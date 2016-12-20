<?php
	$this_is_page = true;

	include 'back/init.php';


	$session = new Session();

	if(!$session->user->is_authorized()) {
		Framework::redirect('./index.php');
	}

	if(!$session->user->checkRole(['admin', 'editor'])) {
		Framework::httpError(403);
	}



	define('ALERTS_FILE_SPLIT_STEP', 2000);


	$conn = Framework::getDBConn();

	$method = Framework::getRequestMethod();
	$request = Framework::getRequestData();


	function get_settlement_data() {
		static $contents = false;
		if($contents == false) {
			$contents = json_decode(file_get_contents('../data/Adm4_Luhansk_Donestk.json'), true);
		}
		return $contents;
	}

	$alert = null;

	if(isset($request['id'])) {
		$alert = $conn->select('alerts', ['[>]user' => ['user_id' => 'id']], [
			'alerts.id'
			, 'alerts.ADMIN1'
			, 'alerts.ADMIN2'
			, 'alerts.ADMIN4'
			, 'alerts.DATE_REFERAL'
			, 'alerts.INFORMANT'
			, 'alerts.REFERRAL_AGENCY'
			, 'alerts.GCA_NGCA'
			, 'alerts.ALERT_TYPE'
			, 'alerts.CONFLICT_RELATED'
			, 'alerts.NEED_TYPE'
			, 'alerts.DESCRIPTION'
			, 'alerts.CONTEXT'
			, 'alerts.AFFECTED'
			, 'alerts.NO_AFFECTED'
			, 'alerts.SOURCE_INFO'
			, 'alerts.CLUSTER'
			, 'alerts.RESPONSE_PARTNER'
			, 'alerts.CONFIRMATION'
			, 'alerts.ACTION'
			, 'alerts.NO_BENEFICIARIES'
			, 'alerts.STATUS'
			, 'alerts.DATE_UPDATE'
			, 'alerts.GAP_BENEFICIARIES'
			, 'alerts.UNCOVERED_NEEDS'
			, 'alerts.ADDITIONAL_INFO_LINK'
			, 'alerts.COMMENTS'
			, 'user.id' => 'user_id'
		], ['alerts.id' => $request['id']]);



		if(count($alert) == 1) {
			$alert = $alert[0];
			$alert['ADMIN1'] = 'c' . $alert['ADMIN1'];
			$alert['ADMIN2'] = 'c' . $alert['ADMIN2'];
			$alert['ADMIN4'] = 'c' . $alert['ADMIN4'];
			$alert['AFFECTED'] = explode(',', $alert['AFFECTED']);
		} else {
			$alert = null;
		}

		if(!$alert) {
			Framework::httpError(404);
		}

		if(!$session->user->checkRole(['admin']) && $alert['user_id'] != $session->user->id) {
			Framework::httpError(403);
		}
	}

	$template = new Template('tpls/editalert.tpl.php');

	class EditAlertForm extends Form {
		function init() {
			global $alert;
			$this->id = new HiddenField(['required' => false]);
			$this->DATE_REFERAL = new DateField(['label' => new Label(' Referal Date*: ')]);
			$this->INFORMANT = new CharField(['label' => new Label(' Informant: '), 'required' => false]);
			$this->REFERRAL_AGENCY = new CharField(['label' => new Label(' Referral Agency: '), 'required' => false]);
			$this->GCA_NGCA = new SelectField(['label' => new Label(' GCA / NGCA*: '), 'values' => ['GCA' => 'GCA', 'NGCA' => 'NGCA']]);
			$this->ALERT_TYPE = new SelectField(['label' => new Label(' Alert Type*: '), 'values' => ['Support Request' => 'Support Request', 'Need Overview' => 'Need Overview', 'Mine Risk' => 'Mine Risk']]);
			$this->CONFLICT_RELATED = new SelectField(['label' => new Label(' Conflict Related*: '), 'values' => ['Yes' => 'Yes', 'No' => 'No']]);
			$this->NEED_TYPE = new SelectField(['label' => new Label(' Needs Type*: '), 'values' => [
				'Access to health services' => 'Access to health services'
				, 'Access to humanitarian assistance' => 'Access to humanitarian assistance'
				, 'Access to NFI' => 'Access to NFI'
				, 'Access to shelter' => 'Access to shelter'
				, 'Access to water' => 'Access to water'
				, 'Other' => 'Other'
				, 'Presence of landmines and/or EWRs' => 'Presence of landmines and/or EWRs'
			]]);
			$this->DESCRIPTION = new TextField(['label' => new Label(' Description: '), 'required' => false]);
			$this->CONTEXT = new TextField(['label' => new Label(' Context: '), 'required' => false]);
			$this->AFFECTED = new MultiselectField(['label' => new Label(' Affected*: '), 'values' => [
				'Community' => 'Community'
				, 'Individuals' => 'Individuals'
				, 'IDP' => 'IDP'
				, 'NDP' => 'NDP'
			]]);
			$this->NO_AFFECTED = new IntField(['label' => new Label(' Number of Affected*: ')]);
			$this->SOURCE_INFO = new CharField(['label' => new Label(' Info Source: '), 'required' => false]);
			$this->CLUSTER = new SelectField(['label' => new Label(' Cluster*: '), 'values' => [
				'Education' => 'Education'
				, 'Food Security' => 'Food Security'
				, 'Health/Nutrition' => 'Health/Nutrition'
				, 'Livelihoods/Early Recovery' => 'Livelihoods/Early Recovery'
				, 'Logistics' => 'Logistics'
				, 'Protection' => 'Protection'
				, 'Emergency Shelter/NFI' => 'Emergency Shelter/NFI'
				, 'Water Sanitation Hygiene' => 'Water Sanitation Hygiene'
			]]);
			$this->RESPONSE_PARTNER = new CharField(['label' => new Label(' Response Partner: '), 'required' => false]);
			$this->CONFIRMATION = new SelectField(['label' => new Label(' Confirmation*: '), 'values' => ['Yes' => 'Yes', 'No' => 'No']]);
			$this->ACTION = new CharField(['label' => new Label(' Action: '), 'required' => false]);
			$this->NO_BENEFICIARIES = new IntField(['label' => new Label(' Number of Beneficiaries: '), 'required' => false]);
			$this->STATUS = 
			new SelectField(['label' => new Label(' Status*: '), 'values' => [
				'resolved' => 'Resolved'
				, 'Addressed but unresolved' => 'Addressed, not resolved'
				, 'Not addressed' => 'Not addressed'
			]]);
			$this->DATE_UPDATE = new DateField(['label' => new Label(' Update Date: '), 'required' => false]);
			$this->GAP_BENEFICIARIES = new IntField(['label' => new Label(' Gap of Beneficiaries: '), 'required' => false]);
			$this->UNCOVERED_NEEDS = new CharField(['label' => new Label(' Uncovered Needs: '), 'required' => false]);
			$this->ADDITIONAL_INFO_LINK = new CharField(['label' => new Label(' Additional Info Link: '), 'required' => false]);
			$this->COMMENTS = new TextField(['label' => new Label(' Comments: '), 'required' => false]);
			$this->ADMIN1 = new HiddenField(['required' => true]);
			$this->ADMIN2 = new HiddenField(['required' => true]);
			$this->ADMIN4 = new HiddenField(['required' => true]);
		}
	}

	$form = new EditAlertForm();

	/*----------  check that values will not broke data integrity  ----------*/
	$form->ADMIN1->validator = function($value) {
		global $form;
		$data = get_settlement_data();
		return isset($data[$form->ADMIN1->value]);
	};
	$form->ADMIN2->validator = function($value) {
		global $form;
		$data = get_settlement_data();
		return isset($data[$form->ADMIN1->value])
			&& isset($data[$form->ADMIN1->value][1][$form->ADMIN2->value])
		;
	};
	$form->ADMIN4->validator = function($value) {
		global $form;
		$data = get_settlement_data();
		return isset($data[$form->ADMIN1->value])
			&& isset($data[$form->ADMIN1->value][1][$form->ADMIN2->value])
			&& isset($data[$form->ADMIN1->value][1][$form->ADMIN2->value][1][$form->ADMIN4->value])
		;
	};


	if($method == 'POST') {
		$form->validate($request);

		if($form->is_valid) {

			$oblast = get_settlement_data()[$form->ADMIN1->value];
			$raion = $oblast[1][$form->ADMIN2->value];
			$settlement = $raion[1][$form->ADMIN4->value];

			$data = [
				'DATE_REFERAL' => $form->DATE_REFERAL->value
				, 'INFORMANT' => $form->INFORMANT->value
				, 'REFERRAL_AGENCY' => $form->REFERRAL_AGENCY->value
				, 'OBLAST' => $oblast[0]
				, 'RAION' => $raion[0]
				, 'SETTLEMENTS' => $settlement[0]

				// clean admin code from 'c' character at start
				, 'ADMIN1' => substr($form->ADMIN1->value, 1)
				, 'ADMIN2' => substr($form->ADMIN2->value, 1)
				, 'ADMIN4' => substr($form->ADMIN4->value, 1)

				, 'GCA_NGCA' => $form->GCA_NGCA->value
				, 'ALERT_TYPE' => $form->ALERT_TYPE->value
				, 'CONFLICT_RELATED' => $form->CONFLICT_RELATED->value
				, 'NEED_TYPE' => $form->NEED_TYPE->value
				, 'DESCRIPTION' => $form->DESCRIPTION->value
				, 'CONTEXT' => $form->CONTEXT->value

				, 'AFFECTED' => implode(',', $form->AFFECTED->value)
				
				, 'NO_AFFECTED' => $form->NO_AFFECTED->value
				, 'SOURCE_INFO' => $form->SOURCE_INFO->value
				, 'CLUSTER' => $form->CLUSTER->value
				, 'RESPONSE_PARTNER' => $form->RESPONSE_PARTNER->value
				, 'CONFIRMATION' => $form->CONFIRMATION->value
				, 'ACTION' => $form->ACTION->value
				, 'NO_BENEFICIARIES' => $form->NO_BENEFICIARIES->value
				, 'STATUS' => $form->STATUS->value
				, 'DATE_UPDATE' => $form->DATE_UPDATE->value
				, 'GAP_BENEFICIARIES' => $form->GAP_BENEFICIARIES->value
				, 'UNCOVERED_NEEDS' => $form->UNCOVERED_NEEDS->value
				, 'ADDITIONAL_INFO_LINK' => $form->ADDITIONAL_INFO_LINK->value
				, 'COMMENTS' => $form->COMMENTS->value
				, 'longitude' => $settlement[1]
				, 'latitude' => $settlement[2]
			];

			// check if alert updates or this is new
			if($alert) {
				$conn->update('alerts', $data, ['id' => $alert['id']]);
				$session->message('Alert updated!');

				$cur_id = $alert['id'];
			} else {
				$data['user_id'] = $session->user->id;
				$conn->insert('alerts', $data);
				$session->message('Alert added!');


				$cur_id = $conn->query('SELECT SCOPE_IDENTITY()')->fetch()[0];
			}

			for($num = 0; $num * ALERTS_FILE_SPLIT_STEP < $cur_id; ++$num);

			// export updated data
			$all_alerts = $conn->select('alerts', [
				'DATE_REFERAL'
				, 'OBLAST'
				, 'RAION'
				, 'SETTLEMENTS'
				, 'ADMIN1'
				, 'ADMIN2'
				, 'ADMIN4'
				, 'INFORMANT'
				, 'REFERRAL_AGENCY'
				, 'GCA_NGCA'
				, 'ALERT_TYPE'
				, 'CONFLICT_RELATED'
				, 'NEED_TYPE'
				, 'DESCRIPTION'
				, 'CONTEXT'
				, 'AFFECTED'
				, 'NO_AFFECTED'
				, 'SOURCE_INFO'
				, 'CLUSTER'
				, 'RESPONSE_PARTNER'
				, 'CONFIRMATION'
				, 'ACTION'
				, 'NO_BENEFICIARIES'
				, 'STATUS'
				, 'DATE_UPDATE'
				, 'GAP_BENEFICIARIES'
				, 'UNCOVERED_NEEDS'
				, 'ADDITIONAL_INFO_LINK'
				, 'COMMENTS'
				, 'latitude'
				, 'longitude'
			], [
				'AND' => [
					'id[>=]' => ($num - 1) * ALERTS_FILE_SPLIT_STEP
					, 'id[<]' => $num * ALERTS_FILE_SPLIT_STEP
				]
			]);


			/* write csv line by line into temporary file resource, then everything write to file */

			$data = fopen('php://memory', 'rw');

			fputcsv($data, array_keys($all_alerts[0]));
			foreach($all_alerts as $alert) {
				fputcsv($data, $alert);
			}

			rewind($data);

			$data = stream_get_contents($data);

			file_put_contents('../data/alerts/alerts_' . $num . '.csv', $data);

			Framework::redirect('./admin.php');
		}
	} else { // handle GET and all other stuff here
		if($alert) {
			// set initial data if this is not new alert
			$form->validate($alert);
		}
	}

	echo $template->render([
		'user' => $session->user
		, 'nav' => 'alert_management'
		, 'messages' => $session->fetch_messages()
		, 'form' => $form
	]);