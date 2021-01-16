<?php 
    namespace VirtualLab\Controllers;
    use VirtualLab\Models\Model;

    class Device extends Controller {
        public function __construct() {
            parent::__construct();
        }

        public function create($request) {
            $property = $request->body->property;
            $productKey = $request->body->product_key;

            if (!is_numeric($productKey) || strlen($productKey) != \VirtualLab::PRODUCT_KEY_SIZE) {
                $errorMessages['product_key'] = 'Invalid product key';
            }

            if (sizeof($errorMessages) == 0) {
                $this->dbConnection->open();
                $device = Model::findOne(
                    $this->dbConnection,
                    array('product_key' => $productKey),
                    'devices'
                );

                if ($device) {     
                    $this->jsonResponse(array('success' => false, 'message' => array('product_key' => 'Product key already exists')));
                }

                if (Model::create(
                    $this->dbConnection,
                    array('property' => $property, 'product_key' => $productKey),
                    'devices'
                )) {
                    $this->jsonResponse(array('success' => true, 'message' => 'Created successfully'));
                }

                $this->jsonResponse(array('success' => false, 'message' => 'Server error'));
            }

            $this->jsonResponse(array('success' => false, 'message' => $errorMessages));
        }
    }

?>