<?php
	$this_is_page = true;
	
	include 'back/init.php';

	$template = new Template('tpls/admin.tpl.php');

	$session = new Session();

	if(!$session->user->is_authorized()) {
		Framework::redirect('./index.php');
	}

	if(!$session->user->checkRole(['admin', 'editor'])) {
		Framework::httpError(403);
	}

	$request = Framework::getRequestData();

	$conn = Framework::getDBConn();

	$total = $conn->count('alerts', $session->user->checkRole(['admin']) ? [] : ['user_id' => $session->user->id]);

	define('PAGINATION_STEP', 100);

	$page_num_max = ceil($total / PAGINATION_STEP) - 1;

	if(isset($request['page'])) {
		$request['page'] = intval($request['page']);
		if($request['page'] <= $page_num_max && $request['page'] >= 0) {
			$page_num = $request['page'];
		}
	}

	if(!isset($page_num)) {
		$page_num = $page_num_max;
	}

	$lo = $page_num * PAGINATION_STEP;
	$hi = ($page_num + 1) * PAGINATION_STEP;

	$query = '
		SELECT
			"alerts"."id"
			,"alerts"."DATE_REFERAL"
			,"alerts"."INFORMANT"
			,"alerts"."REFERRAL_AGENCY"
			,"alerts"."OBLAST"
			,"alerts"."ADMIN1"
			,"alerts"."RAION"
			,"alerts"."ADMIN2"
			,"alerts"."SETTLEMENTS"
			,"alerts"."ADMIN4"
			,"alerts"."GCA_NGCA"
			,"alerts"."ALERT_TYPE"
			,"alerts"."CONFLICT_RELATED"
			,"alerts"."NEED_TYPE"
			,"alerts"."DESCRIPTION"
			,"alerts"."CONTEXT"
			,"alerts"."AFFECTED"
			,"alerts"."NO_AFFECTED"
			,"alerts"."SOURCE_INFO"
			,"alerts"."CLUSTER"
			,"alerts"."RESPONSE_PARTNER"
			,"alerts"."CONFIRMATION"
			,"alerts"."ACTION"
			,"alerts"."NO_BENEFICIARIES"
			,"alerts"."STATUS"
			,"alerts"."DATE_UPDATE"
			,"alerts"."GAP_BENEFICIARIES"
			,"alerts"."UNCOVERED_NEEDS"
			,"alerts"."ADDITIONAL_INFO_LINK"
			,"alerts"."COMMENTS"
			,"alerts"."latitude"
			,"alerts"."longitude"
			,"user"."email"
		FROM "alerts" LEFT JOIN "user" ON "alerts"."user_id" = "user"."id"
		' . ( $session->user->checkRole(['admin']) ? '' : ' WHERE "alerts"."user_id" = ' . $session->user->id ) . '
		ORDER BY "alerts"."id" DESC
		OFFSET ' . max(0, $total - $hi) . ' ROWS FETCH NEXT ' . min(PAGINATION_STEP, $total - $lo) . ' ROWS ONLY
	';


	$query_result = $conn->query($query);
	

	$data = $query_result->fetchAll();

	echo $template->render([
		'user' => $session->user
		, 'nav' => 'alert_management'
		, 'messages' => $session->fetch_messages()
		, 'data' => $data
		, 'page_num' => $page_num
		, 'page_num_max' => $page_num_max
	]);
