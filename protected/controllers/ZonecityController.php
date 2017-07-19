<?php
/**
 * ZonecityController
 * @var $this ZonecityController
 * @var $model OmmuZoneCity
 * @var $form CActiveForm
 * version: 1.3.0
 * Reference start
 *
 * TOC :
 *	Index
 *	Suggest
 *	Manage
 *	Add
 *	Edit
 *	RunAction
 *	Delete
 *	Publish
 *
 *	LoadModel
 *	performAjaxValidation
 *
 * @author Putra Sudaryanto <putra@sudaryanto.id>
 * @copyright Copyright (c) 2015 Ommu Platform (opensource.ommu.co)
 * @link https://github.com/ommu/core
 * @contact (+62)856-299-4114
 *
 *----------------------------------------------------------------------------------------------------------
 */

class ZonecityController extends Controller
{
	/**
	 * @var string the default layout for the views. Defaults to '//layouts/column2', meaning
	 * using two-column layout. See 'protected/views/layouts/column2.php'.
	 */
	//public $layout='//layouts/column2';
	public $defaultAction = 'index';

	/**
	 * Initialize admin page theme
	 */
	public function init() 
	{
		if(!Yii::app()->user->isGuest) {
			if(in_array(Yii::app()->user->level, array(1,2))) {
				$arrThemes = Utility::getCurrentTemplate('admin');
				Yii::app()->theme = $arrThemes['folder'];
				$this->layout = $arrThemes['layout'];
			} else
				throw new CHttpException(404, Yii::t('phrase', 'The requested page does not exist.'));
		} else {
			$arrThemes = Utility::getCurrentTemplate('public');
			Yii::app()->theme = $arrThemes['folder'];
			$this->layout = $arrThemes['layout'];
		}
	}

	/**
	 * @return array action filters
	 */
	public function filters() 
	{
		return array(
			'accessControl', // perform access control for CRUD operations
			//'postOnly + delete', // we only allow deletion via POST request
		);
	}

	/**
	 * Specifies the access control rules.
	 * This method is used by the 'accessControl' filter.
	 * @return array access control rules
	 */
	public function accessRules() 
	{
		return array(
			array('allow',  // allow all users to perform 'index' and 'view' actions
				'actions'=>array('index'),
				'users'=>array('*'),
			),
			array('allow', // allow authenticated user to perform 'create' and 'update' actions
				'actions'=>array('suggest'),
				'users'=>array('@'),
				'expression'=>'isset(Yii::app()->user->level)',
				//'expression'=>'isset(Yii::app()->user->level) && (Yii::app()->user->level != 1)',
			),
			array('allow', // allow authenticated user to perform 'create' and 'update' actions
				'actions'=>array('manage','add','edit','runaction','delete','publish'),
				'users'=>array('@'),
				'expression'=>'isset(Yii::app()->user->level) && (Yii::app()->user->level == 1)',
			),
			array('allow', // allow admin user to perform 'admin' and 'delete' actions
				'actions'=>array(),
				'users'=>array('admin'),
			),
			array('deny',  // deny all users
				'users'=>array('*'),
			),
		);
	}
	
	/**
	 * Lists all models.
	 */
	public function actionIndex() 
	{
		$this->redirect(array('manage'));
	}

	/**
	 * Lists all models.
	 */
	public function actionSuggest($id=null, $limit=10) 
	{
		if($id == null) {
			if(isset($_GET['term'])) {
				$criteria = new CDbCriteria;
				$criteria->select = "city_id, province_id, city_name";
				$criteria->condition = 'city_name LIKE :city';
				$criteria->params = array(':city' => '%' . strtolower($_GET['term']) . '%');
				$criteria->order = "city_name ASC";
				$criteria->limit = $limit;
				$model = OmmuZoneCity::model()->findAll($criteria);

				if($model) {
					foreach($model as $items) {
						$result[] = array(
							'id' => $items->city_id, 
							'value' => $items->city_name,
							'province_id' => $items->province->province_id,
							'province_name' => $items->province->province_name,
							'country_id' => $items->province->country->country_id,
							'country_name' => $items->province->country->country_name,
						);
					}
				}
			}
			echo CJSON::encode($result);
			Yii::app()->end();
			
		} else {
			$model = OmmuZoneCity::getCity($id);
			$message['data'] = '<option value="">'.Yii::t('phrase', 'Select one').'</option>';
			foreach($model as $key => $val) {
				$message['data'] .= '<option value="'.$key.'">'.$val.'</option>';
			}
			echo CJSON::encode($message);			
		}
	}

	/**
	 * Manages all models.
	 */
	public function actionManage() 
	{
		$model=new OmmuZoneCity('search');
		$model->unsetAttributes();  // clear any default values
		if(isset($_GET['OmmuZoneCity'])) {
			$model->attributes=$_GET['OmmuZoneCity'];
		}

		$columnTemp = array();
		if(isset($_GET['GridColumn'])) {
			foreach($_GET['GridColumn'] as $key => $val) {
				if($_GET['GridColumn'][$key] == 1) {
					$columnTemp[] = $key;
				}
			}
		}
		$columns = $model->getGridColumn($columnTemp);

		$this->pageTitle = Yii::t('phrase', 'Cities');
		$this->pageDescription = '';
		$this->pageMeta = '';
		$this->render('/zone_city/admin_manage',array(
			'model'=>$model,
			'columns' => $columns,
		));
	}	
	
	/**
	 * Creates a new model.
	 * If creation is successful, the browser will be redirected to the 'view' page.
	 */
	public function actionAdd() 
	{
		$model=new OmmuZoneCity;

		// Uncomment the following line if AJAX validation is needed
		$this->performAjaxValidation($model);

		if(isset($_POST['OmmuZoneCity'])) {
			$model->attributes=$_POST['OmmuZoneCity'];
			
			$jsonError = CActiveForm::validate($model);
			if(strlen($jsonError) > 2) {
				echo $jsonError;

			} else {
				if(isset($_GET['enablesave']) && $_GET['enablesave'] == 1) {
					if($model->save()) {
						echo CJSON::encode(array(
							'type' => 5,
							'get' => Yii::app()->controller->createUrl('manage'),
							'id' => 'partial-ommu-zone-city',
							'msg' => '<div class="errorSummary success"><strong>'.Yii::t('phrase', 'City success created.').'</strong></div>',
						));
					} else {
						print_r($model->getErrors());
					}
				}
			}
			Yii::app()->end();
			
		} else {
			$this->dialogDetail = true;
			$this->dialogGroundUrl = Yii::app()->controller->createUrl('manage');
			$this->dialogWidth = 600;
			
			$this->pageTitle = Yii::t('phrase', 'Create City');
			$this->pageDescription = '';
			$this->pageMeta = '';
			$this->render('/zone_city/admin_add',array(
				'model'=>$model,
			));			
		}
	}

	/**
	 * Updates a particular model.
	 * If update is successful, the browser will be redirected to the 'view' page.
	 * @param integer $id the ID of the model to be updated
	 */
	public function actionEdit($id) 
	{
		$model=$this->loadModel($id);

		// Uncomment the following line if AJAX validation is needed
		$this->performAjaxValidation($model);

		if(isset($_POST['OmmuZoneCity'])) {
			$model->attributes=$_POST['OmmuZoneCity'];
			
			$jsonError = CActiveForm::validate($model);
			if(strlen($jsonError) > 2) {
				echo $jsonError;

			} else {
				if(isset($_GET['enablesave']) && $_GET['enablesave'] == 1) {
					if($model->save()) {
						echo CJSON::encode(array(
							'type' => 5,
							'get' => Yii::app()->controller->createUrl('manage'),
							'id' => 'partial-ommu-zone-city',
							'msg' => '<div class="errorSummary success"><strong>'.Yii::t('phrase', 'City success updated.').'</strong></div>',
						));
					} else {
						print_r($model->getErrors());
					}
				}
			}
			Yii::app()->end();
			
		} else {
			$this->dialogDetail = true;
			$this->dialogGroundUrl = Yii::app()->controller->createUrl('manage');
			$this->dialogWidth = 600;
			
			$this->pageTitle = Yii::t('phrase', 'Update City: $city_name', array('$city_name'=>$model->city_name));
			$this->pageDescription = '';
			$this->pageMeta = '';
			$this->render('/zone_city/admin_edit',array(
				'model'=>$model,
			));			
		}
	}

	/**
	 * Displays a particular model.
	 * @param integer $id the ID of the model to be displayed
	 */
	public function actionRunAction() {
		$id       = $_POST['trash_id'];
		$criteria = null;
		$actions  = $_GET['action'];

		if(count($id) > 0) {
			$criteria = new CDbCriteria;
			$criteria->addInCondition('id', $id);

			if($actions == 'publish') {
				OmmuZoneCity::model()->updateAll(array(
					'publish' => 1,
				),$criteria);
			} elseif($actions == 'unpublish') {
				OmmuZoneCity::model()->updateAll(array(
					'publish' => 0,
				),$criteria);
			} elseif($actions == 'trash') {
				OmmuZoneCity::model()->updateAll(array(
					'publish' => 2,
				),$criteria);
			} elseif($actions == 'delete') {
				OmmuZoneCity::model()->deleteAll($criteria);
			}
		}

		// if AJAX request (triggered by deletion via admin grid view), we should not redirect the browser
		if(!isset($_GET['ajax'])) {
			$this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('manage'));
		}
	}

	/**
	 * Deletes a particular model.
	 * If deletion is successful, the browser will be redirected to the 'admin' page.
	 * @param integer $id the ID of the model to be deleted
	 */
	public function actionDelete($id) 
	{
		$model=$this->loadModel($id);
		
		if(Yii::app()->request->isPostRequest) {
			// we only allow deletion via POST request
			if(isset($id)) {
				if($model->delete()) {
					echo CJSON::encode(array(
						'type' => 5,
						'get' => Yii::app()->controller->createUrl('manage'),
						'id' => 'partial-ommu-zone-city',
						'msg' => '<div class="errorSummary success"><strong>'.Yii::t('phrase', 'City success deleted.').'</strong></div>',
					));
				}
			}

		} else {
			$this->dialogDetail = true;
			$this->dialogGroundUrl = Yii::app()->controller->createUrl('manage');
			$this->dialogWidth = 350;

			$this->pageTitle = Yii::t('phrase', 'Delete City: $city_name', array('$city_name'=>$model->city_name));
			$this->pageDescription = '';
			$this->pageMeta = '';
			$this->render('/zone_city/admin_delete');
		}
	}

	/**
	 * Deletes a particular model.
	 * If deletion is successful, the browser will be redirected to the 'admin' page.
	 * @param integer $id the ID of the model to be deleted
	 */
	public function actionPublish($id) 
	{
		$model=$this->loadModel($id);
		
		if($model->publish == 1) {
			$title = Yii::t('phrase', 'Unpublish');
			$replace = 0;
		} else {
			$title = Yii::t('phrase', 'Publish');
			$replace = 1;
		}
		$pageTitle = Yii::t('phrase', '$title City: $city_name', array('$title'=>$title, '$city_name'=>$model->city_name));

		if(Yii::app()->request->isPostRequest) {
			// we only allow deletion via POST request
			if(isset($id)) {
				//change value active or publish
				$model->publish = $replace;

				if($model->update()) {
					echo CJSON::encode(array(
						'type' => 5,
						'get' => Yii::app()->controller->createUrl('manage'),
						'id' => 'partial-ommu-zone-city',
						'msg' => '<div class="errorSummary success"><strong>'.Yii::t('phrase', 'City success updated.').'</strong></div>',
					));
				}
			}

		} else {
			$this->dialogDetail = true;
			$this->dialogGroundUrl = Yii::app()->controller->createUrl('manage');
			$this->dialogWidth = 350;

			$this->pageTitle = $pageTitle;
			$this->pageDescription = '';
			$this->pageMeta = '';
			$this->render('/zone_city/admin_publish',array(
				'title'=>$title,
				'model'=>$model,
			));
		}
	}

	/**
	 * Returns the data model based on the primary key given in the GET variable.
	 * If the data model is not found, an HTTP exception will be raised.
	 * @param integer the ID of the model to be loaded
	 */
	public function loadModel($id) 
	{
		$model = OmmuZoneCity::model()->findByPk($id);
		if($model===null)
			throw new CHttpException(404, Yii::t('phrase', 'The requested page does not exist.'));
		return $model;
	}

	/**
	 * Performs the AJAX validation.
	 * @param CModel the model to be validated
	 */
	protected function performAjaxValidation($model) 
	{
		if(isset($_POST['ajax']) && $_POST['ajax']==='ommu-zone-city-form') {
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}
}
