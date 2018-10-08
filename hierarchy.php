<?php

function SQLCode($Text) {
	return "'" . str_replace ("'", "''", $Text) . "'";
}

ini_set('display_errors', 'On');
header('Content-Type: text/json; charset=utf-8');

$params = array();
$options =  array( "Scrollable" => SQLSRV_CURSOR_KEYSET );
$arDataJson = array();
$error = array();
$dataResult = array();
$dataHierarchy = array();
$children_array = array();


$server = '*****';
$connectionInfo = array( "Database"=>"*****", "UID"=>"*****", "PWD"=>"*****");


try {
    // Подключение к MSSQL
    $conn = sqlsrv_connect( $server, $connectionInfo);
} catch (Exception $e) {
    array_push($error, $e->getMessage());
}

if( !$conn ) {
    array_push($error, "Connection could not be established.");
}

if(isset($_POST['regID']) and !empty($_POST['regID'])) {
    $regID=$_POST['regID'];
} else {
    $regID=61;
    //array_push($error, "Required DataSet is not available!");
}
/*
// Код региона
$sSQL = "SELECT DISTINCT [ID]
  FROM [PSV_VD].[dbo].[CRM_STRUKTURA_PORTAL_V] 
  WHERE 
    ID_Tip_organizatsii NOT IN ('4D8F2033-906C-E711-80D8-0050569C5347','722970D0-EE75-E811-80E0-0050569C5347','4B8F2033-906C-E711-80D8-0050569C5347')
    AND ID_golovnoy_organizatsii IS NULL
    AND Kod_regiona = " . $regID;
if (!($result = sqlsrv_query($conn, $sSQL,  $params, $options ))) {
    array_push($error, sqlsrv_errors());
    array_push($error, "Ошибка запроса к БД !!!!");
}

$row = sqlsrv_fetch_object($result);
$RegionID = $row->ID;
*/
// todo находить id региона на основе кода региона
$RegionID = '9A01188E-6F10-E711-80D8-0050569C5347';

$sSQL = "SELECT A1.Name as 'Name', A1.psv_FullName as 'FullName', T1.psv_name as 'Type', T2.psv_name as 'SubType', A1.psv_subclienttypeid as 'SubTypeID',
crm_ContactBase.FullName as 'Contact', A1.AccountId as 'AccountId',
ISNULL(A2.AccountId, '". $RegionID ."') as 'MasterID',
A2.Name as 'MasterOrg',
A1.psv_tin as 'INN', A1.psv_kpp as 'KPP',
A1.EMailAddress1 as 'E_mail_1', A1.EMailAddress2 as 'E_mail_2', A1.EMailAddress3 as 'E_mail_3', A1.[Description] as 'Address',
A1.new_description_1 as 'Status',
A1.new_cod_region as 'CodRegion', crm_psv_regionBase.psv_name as 'Region'
  FROM [PSV_STG].[dbo].crm_AccountBase as A1
  left join [PSV_STG].[dbo].crm_psv_regionBase on crm_psv_regionBase.psv_regionId=A1.psv_RegionOKTMO
  left join [PSV_STG].[dbo].crm_psv_clienttypeBase as T1 on A1.psv_clienttypeid=T1.psv_clienttypeId
  left join [PSV_STG].[dbo].crm_psv_clienttypeBase as T2 on A1.psv_subclienttypeid=T2.psv_clienttypeId
  left join [PSV_STG].[dbo].crm_ContactBase on A1.PrimaryContactId=crm_ContactBase.ContactId
  left join [PSV_STG].[dbo].crm_AccountBase as A2 on A1.ParentAccountId=A2.AccountId
WHERE A1.new_cod_region = " . $regID ."
    AND A1.psv_subclienttypeid <> '853B9585-906C-E711-80D8-0050569C5347'
    AND A1.psv_subclienttypeid <> '833B9585-906C-E711-80D8-0050569C5347'
    AND A1.psv_subclienttypeid <> '8B3B9585-906C-E711-80D8-0050569C5347'";


if (!($result = sqlsrv_query($conn, $sSQL,  $params, $options ))) {
    array_push($error, sqlsrv_errors());
    array_push($error, "Ошибка запроса к БД !!!!");
}

try {
     while ($row = sqlsrv_fetch_object($result)) {
        $row_array =  json_decode(json_encode($row), True);
        $dataResult[] = $row_array;

     }
} catch (Exception $e) {
    array_push($error, $e->getMessage());
}


foreach($dataResult as $key => $item){
    if($item['MasterID'] == $RegionID){
        array_push($dataHierarchy,$dataResult[$key]);
    }
}

$ElementPushedCount = count($dataResult) - count($dataHierarchy);

while($ElementPushedCount > 1){
    foreach ($dataResult as $key => $item) {
        if ($item['MasterID'] != $RegionID) {
            if(addItem2Hierarchy($dataHierarchy, $dataResult[$key])){$ElementPushedCount--;};
        }
    }
}

function addItem2Hierarchy(&$tree_array,&$item){
    $result = false;
    foreach($tree_array as $key => $row){

        if($row['AccountId'] == $item['MasterID'] && isset($item['pushed'])){continue;}

        if($row['AccountId'] == $item['MasterID']){
            if(!isset($tree_array[$key]['children'])){$tree_array[$key]['children'] = [];}
            array_push($tree_array[$key]['children'],$item);
            $item['pushed'] = 1;
            $result = true;
        } else {
            if (!empty($tree_array['children'])) {
                addItem2Hierarchy($tree_array[$key]['children'],$tree_array[$key]);
            }
        }
    }
    return $result;
}


$arDataJson['error'] = $error;
$arDataJson['data'] = $dataHierarchy;

//echo json_encode( $arDataJson, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
echo var_dump($arDataJson);
