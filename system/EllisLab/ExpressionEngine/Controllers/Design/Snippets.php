<?php

namespace EllisLab\ExpressionEngine\Controllers\Design;

use EllisLab\ExpressionEngine\Controllers\Design\Design;
use EllisLab\ExpressionEngine\Library\CP\Pagination;
use EllisLab\ExpressionEngine\Library\CP\Table;
use EllisLab\ExpressionEngine\Library\CP\URL;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2015, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine CP Design\Snippets Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Snippets extends Design {

	protected $msm = FALSE;

	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct();

		if ( ! ee()->cp->allowed_group('can_access_design', 'can_admin_templates'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->stdHeader();

		$this->msm = (ee()->config->item('multiple_sites_enabled') == 'y');
	}

	public function index()
	{
		$vars = array();
		$table = Table::create();
		$columns = array(
			'partial',
			'all_sites',
			'manage' => array(
				'type'	=> Table::COL_TOOLBAR
			),
			array(
				'type'	=> Table::COL_CHECKBOX
			)
		);

		if ( ! $this->msm)
		{
			unset($columns[1]);
		}

		$table->setColumns($columns);

		$data = array();
		$snippets = ee('Model')->get('Snippet')->all();

		$base_url = new URL('design/snippets', ee()->session->session_id());

		foreach($snippets as $snippet)
		{
			if ($snippet->site_id == 0)
			{
				$all_sites = '<b class="yes">' . lang('yes') . '</b>';
			}
			else
			{
				$all_sites = '<b class="no">' . lang('no') . '</b>';
			}
			$datum = array(
				$snippet->snippet_name,
				$all_sites,
				array('toolbar_items' => array(
					'edit' => array(
						'href' => cp_url('design/snippets/edit/' . $snippet->snippet_id),
						'title' => lang('edit')
					),
					'find' => array(
						'href' => cp_url('design/snippets/find/' . $snippet->snippet_id),
						'title' => lang('find')
					),
				)),
				array(
					'name' => 'selection[]',
					'value' => $snippet->snippet_id,
					'data'	=> array(
						'confirm' => lang('template_partial') . ': <b>' . htmlentities($snippet->snippet_name, ENT_QUOTES) . '</b>'
					)
				)

			);

			if ( ! $this->msm)
			{
				unset($datum[1]);
			}
			$data[] = $datum;
		}

		$table->setData($data);

		$vars['table'] = $table->viewData($base_url);
		$vars['form_url'] = $vars['table']['base_url'];

		if ( ! empty($vars['table']['data']))
		{
			// Paginate!
			$pagination = new Pagination(
				$vars['table']['limit'],
				$vars['table']['total_rows'],
				$vars['table']['page']
			);
			$vars['pagination'] = $pagination->cp_links($base_url);
		}

		ee()->javascript->set_global('lang.remove_confirm', lang('template_partial') . ': <b>### ' . lang('template_partials') . '</b>');
		ee()->cp->add_js_script(array(
			'file' => array('cp/v3/confirm_remove'),
		));

		$this->stdHeader();
		ee()->view->cp_page_title = lang('template_manager');
		ee()->view->cp_heading = lang('template_partials_header');
		ee()->cp->render('design/snippets/index', $vars);
	}

	public function create()
	{
		$vars = array(
			'ajax_validate' => TRUE,
			'base_url' => cp_url('design/snippets/create'),
			'save_btn_text' => 'btn_create_partial',
			'save_btn_text_working' => 'btn_create_partial_working',
			'sections' => array(
				array(
					array(
						'title' => 'snippet_name',
						'desc' => 'snippet_name_desc',
						'fields' => array(
							'snippet_name' => array(
								'type' => 'text',
								'required' => TRUE
							)
						)
					),
					array(
						'title' => 'snippet_contents',
						'desc' => 'snippet_contents_desc',
						'wide' => TRUE,
						'fields' => array(
							'snippet_contents' => array(
								'type' => 'textarea',
								'required' => TRUE
							)
						)
					),
				)
			)
		);

		if ($this->msm)
		{
			$vars['sections'][0][] = array(
				'title' => 'enable_on_all_sites',
				'desc' => 'enable_on_all_sites_desc',
				'fields' => array(
					'site_id' => array(
						'type' => 'inline_radio',
						'choices' => array(
							'0' => 'enable',
							ee()->config->item('site_id') => 'disable'
						)
					)
				)
			);
		}
		else
		{
			$vars['form_hidden'] = array(
				'site_id' => ee()->config->item('site_id')
			);
		}

		ee()->load->library('form_validation');
		ee()->form_validation->set_rules(array(
			array(
				'field' => 'snippet_name',
				'label' => 'lang:snippet_name',
				'rules' => 'required|callback__snippet_name_checks'
			),
			array(
				'field' => 'snippet_contents',
				'label' => 'lang:snippet_contents',
				'rules' => 'required'
			)
		));

		if (AJAX_REQUEST)
		{
			ee()->form_validation->run_ajax();
			exit;
		}
		elseif (ee()->form_validation->run() !== FALSE)
		{
			$snippet = ee('Model')->make('Snippet');
			$snippet->site_id = ee()->input->post('site_id');
			$snippet->snippet_name = ee()->input->post('snippet_name');
			$snippet->snippet_contents = ee()->input->post('snippet_contents');
			$snippet->save();

			ee('Alert')->makeInline('settings-form')
				->asSuccess()
				->withTitle(lang('create_template_partial_success'))
				->addToBody(sprintf(lang('create_template_partial_success_desc'), $snippet->snippet_name))
				->defer();

			ee()->functions->redirect(cp_url('design/snippets'));
		}
		elseif (ee()->form_validation->errors_exist())
		{
			ee('Alert')->makeInline('settings-form')
				->asIssue()
				->withTitle(lang('create_template_partial_error'))
				->addToBody(lang('create_template_partial_error_desc'));
		}

		ee()->view->cp_page_title = lang('create_partial');

		// ee()->cp->add_js_script(array(
		// 		'plugin'	=> 'ee_codemirror',
		// 		'file'		=> array(
		// 			'codemirror/codemirror',
		// 			'codemirror/closebrackets',
		// 			'codemirror/overlay',
		// 			'codemirror/xml',
		// 			'codemirror/css',
		// 			'codemirror/javascript',
		// 			'codemirror/htmlmixed',
		// 			'codemirror/ee-mode',
		// 			'codemirror/dialog',
		// 			'codemirror/searchcursor',
		// 			'codemirror/search',
		//
		// 			'cp/snippet_editor',
		// 		)
		// 	)
		// );

		ee()->cp->render('settings/form', $vars);
	}

	public function edit($snippet_name)
	{

	}

	/**
	  *	 Check Snippet Name
	  */
	public function _snippet_name_checks($str)
	{
		if ( ! preg_match("#^[a-zA-Z0-9_\-/]+$#i", $str))
		{
			ee()->lang->loadfile('admin');
			ee()->form_validation->set_message('_snippet_name_checks', lang('illegal_characters'));
			return FALSE;
		}

		if (in_array($str, ee()->cp->invalid_custom_field_names()))
		{
			ee()->form_validation->set_message('_snippet_name_checks', lang('reserved_name'));
			return FALSE;
		}

		$snippets = ee('Model')->get('Snippet');
		if ($this->msm)
		{
				$snippets->orFilterGroup()
					->filter('site_id', ee()->config->item('site_id'))
					->filter('site_id', 0)
					->endFilterGroup();
		}
		else
		{
			$snippets->filter('site_id', ee()->config->item('site_id'));
		}
		$count = $snippets->filter('snippet_name', $str)->count();

		if ((strtolower($this->input->post('old_name')) != strtolower($str)) AND $count > 0)
		{
			$this->form_validation->set_message('_snippet_name_checks', lang('snippet_name_taken'));
			return FALSE;
		}
		elseif ($count > 1)
		{
			$this->form_validation->set_message('_snippet_name_checks', lang('snippet_name_taken'));
			return FALSE;
		}

		return TRUE;
	}
}
// EOF