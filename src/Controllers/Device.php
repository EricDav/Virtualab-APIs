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
            $activationKey = isset($_POST['activation_key']) ? $_POST['activation_key'] : null;

            if (strlen($productKey) != \VirtualLab::PRODUCT_KEY_SIZE) {
                $errorMessages['product_key'] = 'Invalid product key';
            }
            $this->dbConnection->open();

            if (sizeof($errorMessages) == 0) {
                $this->dbConnection->open();
                $device = Model::findOne(
                    $this->dbConnection,
                    array('product_key' => $productKey),
                    'devices'
                );

                if ($device) {
                    if (Model::update(
                        $this->dbConnection,
                        array('property' => $property, 'activation_key' => $activationKey),
                        array('product_key' => $productKey),
                        'devices'
                    )) {
                        $this->jsonResponse(array('success' => true, 'message' => 'User created successfully'));
                    }

                    $this->jsonResponse(array('success' => false, 'message' => 'Server error'));
                }

                if (Model::create(
                    $this->dbConnection,
                    array('property' => $property, 'product_key' => $productKey, 'activation_key' => $activationKey),
                    'devices'
                )) {
                    $this->jsonResponse(array('success' => true, 'message' => 'Created successfully'));
                }

                $this->jsonResponse(array('success' => false, 'message' => 'Server error'));
            }

            $this->jsonResponse(array('success' => false, 'message' => 'Invalid product key', 'data' => $errorMessages));
        }
    }

?>