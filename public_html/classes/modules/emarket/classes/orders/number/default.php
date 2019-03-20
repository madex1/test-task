<?php
	class defaultOrderNumber implements iOrderNumber {
		protected $order;
		
		public function __construct(order $order) {
			$this->order = $order;
		}

        /**
         * Генерирует новый номер заказа.
         * @return int
         */
		public function number() {
            $lastOrderNumber = regedit::getInstance()->getVal('emarket/lastOrderNumber');
            if ($lastOrderNumber) {
                $number = $lastOrderNumber + 1;
                regedit::getInstance()->setVal('emarket/lastOrderNumber', $number);

                $this->setOrderNumber($number);

                return $number;
            }

            $sel = new selector('objects');
            $sel->types('object-type')->name('emarket', 'order');
            $sel->order('number')->desc();
            $sel->limit(0, 1);
            $number = $sel->first ? ($sel->first->number + 1) : 1;

            regedit::getInstance()->setVal('emarket/lastOrderNumber', $number);

            $this->setOrderNumber($number);

			return $number;
		}

        /**
         * Устанавливает номер заказа.
         * @param int $number номер заказа
         */
        private function setOrderNumber($number) {
            $order = $this->order;
            $order->name = getLabel('order-name-prefix', 'emarket', $number);
            $order->number = $number;
            $order->commit();
        }
	};
?>