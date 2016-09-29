<?php

namespace EllisLab\ExpressionEngine\Service\Database\Backup;

use EllisLab\ExpressionEngine\Library\Filesystem\Filesystem;
use EllisLab\ExpressionEngine\Service\Formatter\FormatterFactory;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 4.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Database Backup Class
 *
 * @package		ExpressionEngine
 * @subpackage	Database
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Backup {

	/**
	 * @var Filesystem object
	 */
	protected $filesystem;

	/**
	 * @var Backup\Query Database Query object
	 */
	protected $query;

	/**
	 * @var String Full path to write backup to
	 */
	protected $file_path;

	/**
	 * @var FormatterFactory Formatter factory object
	 */
	protected $formatter;

	/**
	 * @var boolean When TRUE, writes a file that has one query per line with no
	 * linebreaks in those queries for easy line-by-line consumption by a
	 * restore script
	 */
	protected $compact_file = FALSE;

	/**
	 * @var int Maximum number of rows to work with/process at a given operation,
	 * e.g. this is the max number of rows we will ask to be queried at once,
	 * and this is roughly how many rows will be written to a file before we
	 * decide to advise the caller to start a new request, should they be backing
	 * up via a web interface
	 */
	protected $row_limit = 5000;

	/**
	 * @var int Number of rows exported in the current session for when we need
	 * to export a database conservatively
	 */
	protected $rows_exported = 0;

	/**
	 * Constructor
	 *
	 * @param	Backup\Query     $query     Query object for generating query strings
	 * @param	Filesystem       $filesytem Filesystem object for writing to files
	 * @param	string           $file_path Path to write SQL file to
	 * @param	FormatterFactory $formatter Formatter factory
	 */
	public function __construct(Filesystem $filesystem, Query $query, $file_path, FormatterFactory $formatter)
	{
		$this->filesystem = $filesystem;
		$this->query = $query;
		$this->file_path = $file_path;
		$this->formatter = $formatter;
	}

	/**
	 * Set max row limit; this is mainly for unit testing purposes as ideally
	 * this property will already be set to a reasonable default
	 *
	 * @param	int	$limit	Max number of rows to deal with at once
	 */
	public function setRowLimit($limit)
	{
		$this->row_limit = $limit;
	}

	/**
	 * Class will write a file with comments and helpful whitespace formatting
	 */
	public function makePrettyFile()
	{
		$this->compact_file = FALSE;
		$this->query->makePrettyQueries();
	}

	/**
	 * Class will write a file that has one query per line with no linebreaks in
	 * those queries for easy line-by-line consumption by a restore script
	 */
	public function makeCompactFile()
	{
		$this->compact_file = TRUE;
		$this->query->makeCompactQueries();
	}

	/**
	 * Runs the entire database backup routine
	 */
	public function run()
	{
		$this->startFile();
		$this->writeDropAndCreateStatements();
		$this->writeAllTableInserts();
	}

	/**
	 * Creates/truncates any existing backup file at the specified path and
	 * inserts a header
	 */
	public function startFile()
	{
		// Make sure we have enough space first
		$db_size = $this->getDatabaseSize();
		if ($db_size > $this->filesystem->getFreeDiskSpace(dirname($this->file_path)))
		{
			$db_size = $this->formatter->make('Number', $db_size)->bytes();

			throw new \Exception("There is not enough free disk space to write your backup. $db_size needed.", 1);
		}

		// Truncate file
		$this->filesystem->write($this->file_path, '', TRUE);
		$this->writeSeparator('Database backup generated by ExpressionEngine');

		$this->writeChunk("SET SQL_MODE=\"NO_AUTO_VALUE_ON_ZERO\";");
		$this->writeChunk("SET time_zone = \"+00:00\";");
	}

	/**
	 * Adds up the size of all available tables and returns the result
	 *
	 * @return	int	Size of database in bytes
	 */
	protected function getDatabaseSize()
	{
		$tables = $this->query->getTables();

		$total_size = 0;
		foreach ($tables as $table => $specs)
		{
			$total_size += $specs['size'];
		}

		return $total_size;
	}

	/**
	 * Writes the DROP IF EXISTS and CREATE TABLE statements for each table
	 */
	public function writeDropAndCreateStatements()
	{
		$tables = $this->query->getTables();

		$this->writeSeparator('Drop old tables if exists');

		foreach ($tables as $table => $specs)
		{
			$this->writeChunk($this->query->getDropStatement($table));
		}

		$this->writeSeparator('Create tables and their structure');

		foreach ($tables as $table => $specs)
		{
			$create = $this->query->getCreateForTable($table);

			// Add an extra linebreak if not a compact file
			if ( ! $this->compact_file)
			{
				$create .= "\n";
			}

			$this->writeChunk($create);
		}
	}

	/**
	 * Writes ALL table INSERTs
	 */
	public function writeAllTableInserts()
	{
		$this->writeSeparator('Populate tables with their data');

		foreach ($this->query->getTables() as $table => $specs)
		{
			$returned = $this->writeInsertsForTableWithOffset($table);

			if ($returned['next_offset'] > 0)
			{
				$returned = $this->writeInsertsForTableWithOffset($table, $returned['next_offset']);
			}
		}
	}

	/**
	 * Writes partial INSERTs for a given table, with the idea being a backup
	 * can be split up across multiple requests for large databases
	 *
	 * @param	string	$table_name	Table name
	 * @param	int		$offset		Offset to start the backup from
	 * @return	mixed	FALSE if no more work to do, otherwise an array telling
	 * the caller which table and offset they need to start at next time, e.g.:
	 *	[
	 *		'table_name' => 'exp_some_table'
	 *		'offset'     => 5000
	 *	]
	 */
	public function writeTableInsertsConservatively($table = NULL, $offset = 0)
	{
		$tables = array_keys($this->query->getTables());

		// Table specified? Chop off the beginning of the tables array until we
		// we get to the specified table and start the loop from there
		if ( ! empty($table))
		{
			$tables = array_slice($tables, array_search($table, $tables));
		}

		$this->rows_exported = 0;
		foreach ($tables as $table)
		{
			$total_rows = $this->query->getTotalRows($table);

			// Keep under our row limit
			$limit = $this->row_limit - $this->rows_exported;

			$returned = $this->writeInsertsForTableWithOffset($table, $offset, $limit);

			$this->rows_exported += $returned['rows_exported'];
			$offset = $returned['next_offset'];

			// Have we finished a table AND exported what we consider to be the
			// most number of rows we should export? Start a fresh request with
			// the next table
			if ($this->rows_exported >= $this->row_limit && $offset == 0)
			{
				// Find the next table in the array
				$next_table = array_slice($tables, array_search($table, $tables) + 1, 1);

				if ( ! isset($next_table[0]))
				{
					return FALSE;
				}

				return [
					'table_name' => $next_table[0],
					'offset'     => 0
				];
			}
			// There is more of this table to export that we weren't able to,
			// let the caller know
			elseif ($offset > 0)
			{
				return [
					'table_name' => $table,
					'offset'     => $offset
				];
			}
		}

		return FALSE;
	}

	/**
	 * Writes partial INSERTs for a given table, with the idea being a backup
	 * can be split up across multiple requests for large databases
	 *
	 * @param	string	$table_name	Table name
	 * @param	int		$offset		Offset to start the backup from
	 * @return	array	Array of information to tell the caller the offset the
	 * table should be queried from next, and also the number of rows that were
	 * exported during the call, e.g.:
	 *	[
	 *		'next_offset' => 0,
	 *		'rows_exported' => 50
	 *	]
	 * If next_offset is zero, there are no more rows to export.
	 */
	public function writeInsertsForTableWithOffset($table_name, $offset = 0, $limit = 0)
	{
		$total_rows = $this->query->getTotalRows($table_name);

		// No more rows? We're done here
		if ($total_rows - $offset <= 0)
		{
			return [
				'next_offset' => 0,
				'rows_exported' => 0
			];
		}

		// At least apply the row limit to prevent selecting a million-row table
		// all at once
		$limit = ($limit !== 0) ? $limit : $this->row_limit;

		$inserts = $this->query->getInsertsForTable($table_name, $offset, $limit);

		$this->writeChunk($inserts['insert_string']);

		// Add another line break if not compact
		if ( ! $this->compact_file)
		{
			$this->writeChunk('');
		}

		$next_offset = 0;

		// Still more to go? Notify the caller of the new offset to start from
		if ($total_rows - ($offset + $limit) > 0)
		{
			$next_offset = $offset + $limit;
		}

		return [
			'next_offset' => $next_offset,
			'rows_exported' => $inserts['rows_exported']
		];
	}

	/**
	 * Writes a chunk of text to the file followed by a newline
	 *
	 * @param	string	$chunk	Chunk to write to the file. Sloth love Chunk.
	 */
	protected function writeChunk($chunk)
	{
		$this->filesystem->write($this->file_path, $chunk. "\n", FALSE, TRUE);
	}

	/**
	 * Writes a pretty(ish) separator to the file with a given string of text,
	 * usually to mark a new section in the file
	 *
	 * @param	string	$text	Text to include in the separater
	 */
	protected function writeSeparator($text)
	{
		if ($this->compact_file)
		{
			return;
		}

		$separator = <<<EOT

--
-- $text
--

EOT;
		$this->writeChunk($separator);
	}
}

// EOF
