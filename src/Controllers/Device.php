<?php 
    namespace VirtualLab\Controllers;

    class Device extends Controller {
        public function __construct() {
            parent::__construct();
        }

        public function create($request) {
            $property = $request->body->property;
            $productKey = $request->body->product_key;

            if (!is_numeric($productKey) || strlen($productKey) != \VirtualLab::ACTIVATION_KEY_SIZE) {
                $errorMessages['product_key'] = 'Invalid product key';
            }

            if (sizeof($errorMessages) == 0) {
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