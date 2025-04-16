<?php
// error_reporting(E_ALL);

require_once "include/apiResponseGenerator.php";
require_once "include/dbConnection.php";
class REPORTMODEL extends APIRESPONSE
{
    private function processMethod($data, $loginData)
    {

        switch (REQUESTMETHOD) {
            case 'GET':                
                throw new Exception("Unable to proceed your request!");
                break;
            case 'POST':
                $urlPath = $_GET['url'];
                $urlParam = explode('/', $urlPath);
                if ($urlParam[1] === 'generate' || $urlParam[1] === 'download' ) {
                    $result = $this->generateReport($data, $loginData, $urlParam[1]);
                    return $result;                    
                }
                else {
                    throw new Exception("Unable to proceed your request!");
                }
                break;
            case 'PUT':
                throw new Exception("Unable to proceed your request!");
                break;
            case 'DELETE':
                throw new Exception("Unable to proceed your request!");
                break;
            default:
                $result = $this->handle_error();
                return $result;
                break;
        }
    }
    // Initiate db connection
    private function dbConnect()
    {
        $conn = new DBCONNECTION();
        $db = $conn->connect();
        return $db;
    }

    /**
     * Function is to get the for particular record
     *
     * @param array $data
     * @return multitype:
     */
    public function generateReport($data, $loginData, $type)
    {
        try {
            $db = $this->dbConnect(); 
            $validationData = array("From date"=>$data['fromDate'], "To date"=>$data['toDate']);
            $this->validateInputDetails($validationData);          
            $saleQuery = "SELECT invoice_number,sale_date, customer_name, city, product_type, product_quantity, unit, amount FROM tbl_sales WHERE status = 1 and created_by = 1  and sale_date >='".$data['fromDate']."' and sale_date <= '".$data['toDate']."'";
            $result = $db->query($saleQuery);
            $row_cnt = mysqli_num_rows($result);
            if ($row_cnt > 0) {
                while($sal_data = mysqli_fetch_array($result, MYSQLI_ASSOC)){
                    $sale_data[]= $sal_data;
                }
            }

            $purchaseQuery = "SELECT invoice_number,purchase_date, vendor_name, city, product_type, product_quantity, unit, amount FROM tbl_purchase WHERE status = 1 and created_by = 1  and purchase_date >='".$data['fromDate']."' and purchase_date <= '".$data['toDate']."'";
            $result = $db->query($purchaseQuery);
            $row_cnt = mysqli_num_rows($result);
            if ($row_cnt > 0) {
                while($pur_data = mysqli_fetch_array($result, MYSQLI_ASSOC)){
                    $purchase_data[]= $pur_data;
                }
            }

            $saleAmountQuery="SELECT SUM(amount) as amount FROM tbl_sales WHERE status = 1 and created_by = 1  and sale_date >='".$data['fromDate']."' and sale_date <= '".$data['toDate']."'";
            $sale_amount=$this->totalAmountCalculation($saleAmountQuery);

            $purchaseAmountQuery="SELECT SUM(amount) as amount FROM tbl_purchase WHERE status = 1 and created_by = 1  and purchase_date >='".$data['fromDate']."' and purchase_date <= '".$data['toDate']."'";
            $purchase_amount=$this->totalAmountCalculation($purchaseAmountQuery);

            $lossAmountQuery="SELECT SUM(amount) as amount FROM tbl_loss WHERE status = 1 and created_by = 1  and loss_date >='".$data['fromDate']."' and loss_date <= '".$data['toDate']."'";
            $loss_amount=$this->totalAmountCalculation($lossAmountQuery);
            $totalIncome=$sale_amount;
            $expenses=$purchase_amount;
            $profit=$sale_amount -($purchase_amount+$loss_amount);
            $loss=$loss_amount;
            if($type=="generate"){
                $responseArray = array(
                    "totalIncome"=>$totalIncome,
                    "expenses"=>$expenses,
                    "profit" => $profit,
                    "loss" => $loss,
                    "saleData" => $sale_data,
                    "purchaseData" => $purchase_data


                );
            
                if ($responseArray) {
                    $resultArray = array(
                        "apiStatus" => array(
                            "code" => "200",
                            "message" => "Report details generated successfully"),
                        "result" => $responseArray,
                    );
                    return $resultArray;
               }
           }
           elseif($type=="download"){
                $this->generateExcelReport($sale_data,$purchase_data,$loss_amount,$totalIncome,$profit,$expenses);

           }    
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

public function generateExcelReport($sale_data,$purchase_data,$loss_amount,$totalIncome,$profit,$expenses){
$dateNow = date("Y-m-d");
$file="Report_".$dateNow.".xls";
$html='<!DOCTYPE html>
<html>
<head>
<style>
body{
  font-family: arial, sans-serif;
}
table {
  font-family: arial, sans-serif;
  border-collapse: collapse;
  width: 100%;
}

td, th {
  border: 0px;
  text-align: left;
  padding: 8px;
}
table tbody{
  color: #000000;
}
table td, table th{
  border: 1px solid #fff;
}
table.bordered {
  border: 1px solid #dddddd;
}
table .table-title{
  font-family: "arial";
  font-size: 14px;
  font-weight: bold;
  text-transform: uppercase;
}
table.bg-blue thead{
  background-color: #2676d5;
  color: #ffffff;
}
table.bg-blue tbody tr:nth-child(odd){
  background-color: #84bcff;
}
table.bg-blue tbody tr:nth-child(even){
  background-color: #bbdaff;
}
table.bg-green thead{
  background-color: #38a902;
  color: #ffffff;
}
table.bg-green tbody tr:nth-child(odd){
  background-color: #8ddc78;
}
table.bg-green tbody tr:nth-child(even){
  background-color: #bff8b0;
}

</style>
</head>
<body>

<h2>Report: </h2>

<table width="100%">
  <tr>
    <td colspan="3">
      <table width="100%" class="bordered bg-blue">
        <thead>
          <tr>
            <th width="25%">
              <div class="table-title">Total Income</div>
              <div>'.$totalIncome.'</div>
            </th>
            <th width="25%">
              <div class="table-title">Expenses</div>
              <div>'.$expenses.'</div>
            </th>
            <th width="25%">
              <div class="table-title">Profit</div>
              <div>'.$profit.'</div>
            </th>
            <th width="25%">
              <div class="table-title">Loss</div>
              <div>'.$loss_amount.'</div>
            </th>
          </tr>
        </thead>
      </table>
    </td>
  </tr>
  <tr>
    <td width="48%">
      <table class="bordered bg-blue">
        <thead>
          <tr>
            <th>Invoice Number</th>
            <th>Date</th>
            <th>Customer Name</th>
            <th>City</th>
            <th>Product</th>
            <th>Quantity</th>
            <th>Amount</th>
          </tr>
        </thead>
        <tbody>';
        foreach ($sale_data as $sale) {
            $html.='<tr>
            <td>'.$sale['invoice_number'].'</td>
            <td>'.$sale['sale_date'].'</td>
            <td>'.$sale['customer_name'].'</td>
            <td>'.$sale['city'].'</td>
            <td>'.$sale['product_type'].'</td>
            <td>'.$sale['product_quantity'].$sale['unit'].'</td>
            <td>'.$sale['amount'].'</td>
          </tr>';
          
        }          
        $html.='</tbody>
      </table>
    </td>
    <td width="4%"> </td>
    <td width="48%">
      <table class="bordered bg-green">
        <thead>
          <tr>
            <th>Invoice Number</th>
            <th>Date</th>
            <th>Vendor Name</th>
            <th>City</th>
            <th>Product</th>
            <th>Quantity</th>
            <th>Amount</th>
          </tr>
        </thead>
        <tbody>';
          foreach ($purchase_data as $purchase) {
            $html.='<tr>
            <td>'.$purchase['invoice_number'].'</td>
            <td>'.$purchase['purchase_date'].'</td>
            <td>'.$purchase['vendor_name'].'</td>
            <td>'.$purchase['city'].'</td>
            <td>'.$purchase['product_type'].'</td>
            <td>'.$purchase['product_quantity'].$purchase['unit'].'</td>
            <td>'.$purchase['amount'].'</td>
          </tr>';
          
        }
        $html.='</tbody>
      </table>
    </td>
  </tr>
</table>
</body>
</html>';
header("Content-type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=$file");
echo $html;
exit;
}





    public function totalAmountCalculation($sql){
        try {
            $db = $this->dbConnect();
            $result = $db->query($sql);
            $row_cnt = mysqli_num_rows($result);
            if ($row_cnt > 0) {
                $data = mysqli_fetch_array($result, MYSQLI_ASSOC);
                return $data['amount'];
            }
        } catch (Exception $e) {
        throw new Exception($e->getMessage());
        }
    }
     public function validateInputDetails($validationData) {
        foreach ($validationData as $key => $value) {            
            if (empty($value) || trim($value) == "") {
                throw new Exception($key. " should not be empty!");
            }
        }    
    }

    // Unautherized api request
    private function handle_error()
    {
    }
    /**
     * Function is to process the crud request
     *
     * @param array $request
     * @return array
     */
    public function processList($request, $token)
    {
        try {
            $responseData = $this->processMethod($request, $token);
            $result = $this->response($responseData);
            return $responseData;
        } catch (Exception $e) {
            return array(
                "apiStatus" => array(
                    "code" => "401",
                    "message" => $e->getMessage()),
            );
        }
    }
}
