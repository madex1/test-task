<?php

	namespace UmiCms\Classes\Components\Emarket\Tax\Rate\Vat;

	use UmiCms\Classes\Components\Emarket\Tax\Rate\iVat;
	use UmiCms\Classes\Components\Emarket\Tax\Rate\iParser;
	use UmiCms\Classes\Components\Emarket\Tax\Rate\iCalculator;
	use UmiCms\Classes\Components\Emarket\Tax\Rate\Parser\iFactory as iParserFactory;
	use UmiCms\Classes\Components\Emarket\Serializer\Receipt\Parameter\Facade as ReceiptParameterFacade;

	/**
	 * Класс фасада ставок налога на добавленную стоимость (НДС)
	 * @package UmiCms\Classes\Components\Emarket\Tax\Rate\Vat
	 */
	class Facade extends ReceiptParameterFacade implements iFacade {

		/** @var iCalculator $calculator калькулятор суммы ставки налога */
		private $calculator;

		/** @var iParserFactory парсер ставки налога */
		private $parser;

		/** @inheritdoc */
		public function setCalculator(iCalculator $calculator) {
			$this->calculator = $calculator;
		}

		/** @inheritdoc */
		public function setParser(iParserFactory $parser) {
			$this->parser = $parser;
		}

		/**
		 * @inheritdoc
		 * @throws \privateException
		 */
		public function getTwentyPercentVat() {
			return $this->getTaxObjectByGuid(iFacade::TWENTY_PERCENT_VAT_GUID);
		}

		/**
		 * @inheritdoc
		 * @throws \privateException
		 */
		public function getOrderTax(\order $order) {
			$orderItemListTax = $this->getOrderItemListSumOfTax($order->getItems());
			return $orderItemListTax + $this->getOrderDeliveryTax($order);
		}

		/**
		 * @inheritdoc
		 * @throws \privateException
		 */
		public function getOrderItemTax(\orderItem $orderItem) {
			$tax = $this->getTaxRate($orderItem->getTaxRateId());

			return $this->calculateTax(
				$orderItem->getTotalActualPrice(),
				$this->createParser($tax)
			);
		}

		/**
		 * @inheritdoc
		 * @throws \privateException
		 */
		public function getOrderDeliveryTax(\order $order) {
			try {
				$delivery = \delivery::get($order->getDeliveryId());
			} catch (\coreException $e) {
				return 0;
			}

			$tax = $this->getTaxRate($delivery->getTaxRateId());

			return $this->calculateTax(
				$order->getDeliveryPrice(),
				$this->createParser($tax)
			);
		}

		/** @inheritdoc */
		protected function validateParameter($parameter) {
			if (!$parameter instanceof iVat) {
				$this->throwNotFoundException();
			}
		}

		/**
		 * Возвращает сумму налогов товаров в заказе
		 * @param \orderItem[] $orderItemList список товаров
		 * @return float|int|string
		 * @throws \privateException
		 */
		private function getOrderItemListSumOfTax(array $orderItemList) {
			$taxSum = 0;

			foreach ($orderItemList as $item) {
				if ($item instanceof \orderItem) {
					$taxSum += $this->getOrderItemTax($item);
				}
			}

			return $taxSum;
		}

		/**
		 * Возвращает ставку ндс
		 * @param int $id идентификатор ставки ндс
		 * @return string
		 * @throws \privateException
		 */
		private function getTaxRate($id) {
			return $this->getTaxObject($id)->getRate();
		}

		/**
		 * Возвращает объект ставки налога
		 * @param int $rateId идентификатор ставки налога
		 * @return iVat
		 * @throws \privateException
		 */
		private function getTaxObject($rateId) {
			/** @var iVat $rate */
			$rate = $this->get($rateId);
			return $rate;
		}

		/**
		 * Возвращает объект ставки налога по его guid
		 * @param string $rateGuid строковый идентификатор ставки налога
		 * @return iVat
		 * @throws \privateException
		 */
		private function getTaxObjectByGuid($rateGuid) {
			/** @var iVat $rate */
			$rate = $this->getByGuid($rateGuid);
			return $rate;
		}

		/**
		 * Рассчитывает сумму ндс цены
		 * @param int|float $price цена
		 * @param iParser $parser парсер ставки ндс
		 * @return float|int
		 */
		private function calculateTax($price, iParser $parser) {
			return (!$parser->isZeroRate())
				? $this->calculator->calculate($price, $parser->getRateBase(), $parser->getRate())
				: 0;
		}

		/**
		 * Создает парсер ставки налога ндс
		 * @param string|int $tax ставка налога
		 * @return iParser
		 */
		private function createParser($tax) {
			return $this->parser->create($tax);
		}
	}
