<?php

	/** Класс управления календарем */
	class Calendar {

		/** @var news $module */
		public $module;

		/**
		 * @var int номер первого дня недели.  Первый день недели отображается
		 * в первой колонке календаря. Воскресенье имеет номер 0.
		 */
		protected $startDay = 0;

		/**
		 * @var int номер первого месяца года. Первый месяц года отображается
		 * в первой колонке календаря. Январь имеет номер 1.
		 */
		protected $startMonth = 1;

		/** @var array список из семи названий дней недели. Первым элементом является воскресенье. */
		protected $dayNames = ['S', 'M', 'T', 'W', 'T', 'F', 'S'];

		/** @var array список из 12-ти названий месяцев года. Первым элементом является январь. */
		protected $monthNames = [
			'January',
			'February',
			'March',
			'April',
			'May',
			'June',
			'July',
			'August',
			'September',
			'October',
			'November',
			'December'
		];

		/** @var array список количества дней в каждом месяце года. Первый месяц - январь. */
		protected $daysInMonth = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];

		/** Конструктор */
		public function __construct() {
		}

		/**
		 * Возвращает список из семи названий дней недели. Первым элементом является воскресенье.
		 * @return array
		 */
		public function getDayNames() {
			return $this->dayNames;
		}

		/**
		 * Устанавливает список названий дней недели.
		 * @param string[] $names список названий дней недели. Должен содержать 7 элементов.
		 */
		public function setDayNames($names) {
			$this->dayNames = $names;
		}

		/**
		 * Возвращает список из 12-ти названий месяцев года. Первым элементом является январь.
		 * @return array
		 */
		public function getMonthNames() {
			return $this->monthNames;
		}

		/**
		 * Устанавливает список названий месяцев года.
		 * @param string[] $names список названий месяцев года. Должен содержать 12 элементов.
		 */
		public function setMonthNames($names) {
			$this->monthNames = $names;
		}

		/**
		 * Возвращает номер первого дня недели. Первый день недели отображается
		 * в первой колонке календаря. Воскресенье имеет номер 0.
		 * @return int
		 */
		public function getStartDay() {
			return $this->startDay;
		}

		/**
		 * Устанавливает номер первого дня недели. Первый день недели отображается
		 * в первой колонке календаря. Воскресенье имеет номер 0.
		 * @param int $day номер первого дня недели
		 */
		public function setStartDay($day) {
			$this->startDay = $day;
		}

		/**
		 * Возвращает номер первого месяца года. Первый месяц года отображается
		 * в первой колонке календаря. Январь имеет номер 1.
		 * @return int
		 */
		public function getStartMonth() {
			return $this->startMonth;
		}

		/**
		 * Устанавливет номер первого месяца года. Первый месяц года отображается
		 * в первой колонке календаря. Январь имеет номер 1.
		 * @param int $month номер первого месяца года
		 */
		public function setStartMonth($month) {
			$this->startMonth = $month;
		}

		/**
		 * Возвращает URL, при переходе по которому будет открыт календарь с переданными месяцем/годом.
		 * @param int $month номер месяца года
		 * @param int $year год
		 * @return string
		 */
		public function getCalendarLink($month, $year) {
			$d = getdate(time());

			$ret = '?year=' . $year . '&month=' . $month;

			if ($d['mon'] < $month || $d['year'] < $year) {
				$ret = '';
			}

			return $ret;
		}

		/**
		 * Возвращает ссылку, при переходе по которой будет открыт календарь с переданной датой.
		 * @param int $day номер дня месяца
		 * @param int $month номер месяца года
		 * @param int $year год
		 * @return string
		 * @throws coreException
		 */
		public function getDateLink($day, $month, $year) {
			$umiHierarchy = umiHierarchy::getInstance();

			$dayStart = mktime(0, 0, 0, $month, $day, $year);
			$dayEnd = mktime(23, 59, 59, $month, $day, $year);

			$sel = new selector('pages');
			$sel->types('hierarchy-type')->name('news', 'item');
			$sel->where('publish_time')->between($dayStart, $dayEnd);

			$result = $sel->result();
			$total = $sel->length();

			if (empty($result)) {
				$link = '';
			} else {
				if ($total > 1) {
					$link = '?year=' . $year . '&month=' . $month . '&day=' . $day;
				} else {
					$element = array_shift($result);
					$link = $umiHierarchy->getPathById($element->getId()) . '?year=' . $year . '&month=' . $month;
				}
			}

			return $link;
		}

		/**
		 * Возвращает HTML-представление текущего месяца
		 * @return string
		 */
		public function getCurrentMonthView() {
			$d = getdate(time());
			return $this->getMonthView($d['mon'], $d['year']);
		}

		/**
		 * Возвращает HTML-представление текущего года
		 * @return string
		 */
		public function getCurrentYearView() {
			$d = getdate(time());
			return $this->getYearView($d['year']);
		}

		/**
		 * Возвращает HTML-представление переданного месяца года
		 * @param int $month номер месяца года
		 * @param int $year год
		 * @return string
		 */
		public function getMonthView($month, $year) {
			return $this->getMonthHTML($month, $year);
		}

		/**
		 * Возвращает HTML-представление переданного года
		 * @param int $year год
		 * @return string
		 */
		public function getYearView($year) {
			return $this->getYearHTML($year);
		}

		/**
		 * Возвращает количество дней в месяце с учетом високосного года
		 * @param $month
		 * @param $year
		 * @return int
		 */
		private function getDaysInMonth($month, $year) {
			if ($month < 1 || $month > 12) {
				return 0;
			}

			$d = $this->daysInMonth[$month - 1];

			if ($month == 2) {
				if ($year % 4 == 0) {
					if ($year % 100 == 0) {
						if ($year % 400 == 0) {
							$d = 29;
						}
					} else {
						$d = 29;
					}
				}
			}

			return $d;
		}

		/**
		 * Возвращает HTML-представление для переданого месяца
		 * @param int $m номер месяца года
		 * @param int $y год
		 * @param int $showYear 1 - показывать год в календаре, 0 - не показывать
		 * @return string
		 */
		private function getMonthHTML($m, $y, $showYear = 1) {
			$s = '';

			$a = $this->adjustDate($m, $y);
			$month = $a[0];
			$year = $a[1];

			$daysInMonth = $this->getDaysInMonth($month, $year);
			$date = getdate(mktime(12, 0, 0, $month, 1, $year));

			$first = $date['wday'];
			$monthName = $this->monthNames[$month - 1];

			$prev = $this->adjustDate($month - 1, $year);
			$next = $this->adjustDate($month + 1, $year);

			if ($showYear == 1) {
				$prevMonth = $this->getCalendarLink($prev[0], $prev[1]);
				$nextMonth = $this->getCalendarLink($next[0], $next[1]);
			} else {
				$prevMonth = '';
				$nextMonth = '';
			}

			$header = $monthName . (($showYear > 0) ? ' ' . $year : '');

			$s .= "<table class=\"calendar\">\n";
			$s .= "<tr>\n";
			$s .= '<td align="center" valign="top">' .
				(($prevMonth == '') ? '&nbsp;' : "<a href=\"$prevMonth\">&lt;&lt;</a>") .
				"</td>\n";
			$s .= "<td align=\"center\" valign=\"top\" class=\"calendarHeader\" colspan=\"5\">$header</td>\n";
			$s .= '<td align="center" valign="top">' .
				(($nextMonth == '') ? '&nbsp;' : "<a href=\"$nextMonth\">&gt;&gt;</a>") .
				"</td>\n";
			$s .= "</tr>\n";

			$s .= "<tr>\n";
			$s .= '<td align="center" valign="top" class="calendarHeader">' . $this->dayNames[$this->startDay % 7] .
				"</td>\n";
			$s .= '<td align="center" valign="top" class="calendarHeader">' . $this->dayNames[($this->startDay + 1) % 7] .
				"</td>\n";
			$s .= '<td align="center" valign="top" class="calendarHeader">' . $this->dayNames[($this->startDay + 2) % 7] .
				"</td>\n";
			$s .= '<td align="center" valign="top" class="calendarHeader">' . $this->dayNames[($this->startDay + 3) % 7] .
				"</td>\n";
			$s .= '<td align="center" valign="top" class="calendarHeader">' . $this->dayNames[($this->startDay + 4) % 7] .
				"</td>\n";
			$s .= '<td align="center" valign="top" class="calendarHeader">' . $this->dayNames[($this->startDay + 5) % 7] .
				"</td>\n";
			$s .= '<td align="center" valign="top" class="calendarHeader">' . $this->dayNames[($this->startDay + 6) % 7] .
				"</td>\n";
			$s .= "</tr>\n";

			$d = $this->startDay + 1 - $first;
			while ($d > 1) {
				$d -= 7;
			}

			$today = getdate(time());

			while ($d <= $daysInMonth) {
				$s .= "<tr>\n";

				for ($i = 0; $i < 7; $i++) {
					$class =
						($year == $today['year'] && $month == $today['mon'] &&
							$d == $today['mday']) ? 'calendarToday' : 'calendar';
					$s .= "<td class=\"$class\" align=\"right\" valign=\"top\">";
					if ($d > 0 && $d <= $daysInMonth) {
						$link = $this->getDateLink($d, $month, $year);
						$s .= (($link == '') ? $d : "<a href=\"$link\">$d</a>");
					} else {
						$s .= '&nbsp;';
					}
					$s .= "</td>\n";
					$d++;
				}
				$s .= "</tr>\n";
			}

			$s .= "</table>\n";

			return $s;
		}

		/**
		 * Возвращает HTML-представление для переданного года
		 * @param int $year год
		 * @return string
		 */
		private function getYearHTML($year) {
			$s = '';
			$prev = $this->getCalendarLink(0, $year - 1);
			$next = $this->getCalendarLink(0, $year + 1);

			$s .= "<table class=\"calendar\" border=\"0\">\n";
			$s .= '<tr>';
			$s .= '<td align="center" valign="top" align="left">' .
				(($prev == '') ? '&nbsp;' : "<a href=\"$prev\">&lt;&lt;</a>") .
				"</td>\n";
			$s .= '<td class="calendarHeader" valign="top" align="center">' .
				(($this->startMonth > 1) ? $year . ' - ' . ($year + 1) : $year) . "</td>\n";
			$s .= '<td align="center" valign="top" align="right">' .
				(($next == '') ? '&nbsp;' : "<a href=\"$next\">&gt;&gt;</a>") .
				"</td>\n";
			$s .= "</tr>\n";
			$s .= '<tr>';
			$s .= '<td class="calendar" valign="top">' . $this->getMonthHTML(0 + $this->startMonth, $year, 0) . "</td>\n";
			$s .= '<td class="calendar" valign="top">' . $this->getMonthHTML(1 + $this->startMonth, $year, 0) . "</td>\n";
			$s .= '<td class="calendar" valign="top">' . $this->getMonthHTML(2 + $this->startMonth, $year, 0) . "</td>\n";
			$s .= "</tr>\n";
			$s .= "<tr>\n";
			$s .= '<td class="calendar" valign="top">' . $this->getMonthHTML(3 + $this->startMonth, $year, 0) . "</td>\n";
			$s .= '<td class="calendar" valign="top">' . $this->getMonthHTML(4 + $this->startMonth, $year, 0) . "</td>\n";
			$s .= '<td class="calendar" valign="top">' . $this->getMonthHTML(5 + $this->startMonth, $year, 0) . "</td>\n";
			$s .= "</tr>\n";
			$s .= "<tr>\n";
			$s .= '<td class="calendar" valign="top">' . $this->getMonthHTML(6 + $this->startMonth, $year, 0) . "</td>\n";
			$s .= '<td class="calendar" valign="top">' . $this->getMonthHTML(7 + $this->startMonth, $year, 0) . "</td>\n";
			$s .= '<td class="calendar" valign="top">' . $this->getMonthHTML(8 + $this->startMonth, $year, 0) . "</td>\n";
			$s .= "</tr>\n";
			$s .= "<tr>\n";
			$s .= '<td class="calendar" valign="top">' . $this->getMonthHTML(9 + $this->startMonth, $year, 0) . "</td>\n";
			$s .= '<td class="calendar" valign="top">' . $this->getMonthHTML(10 + $this->startMonth, $year, 0) . "</td>\n";
			$s .= '<td class="calendar" valign="top">' . $this->getMonthHTML(11 + $this->startMonth, $year, 0) . "</td>\n";
			$s .= "</tr>\n";
			$s .= "</table>\n";

			return $s;
		}

		/**
		 * Пересчитывает месяц и год с учетом того, что номер месяца может принимать значения,
		 * большие 12 и меньшие 0. Например, 14-ый месяц 2001 года - это на самом деле 2-ой месяц 2002 года.
		 * @param int $month месяц года
		 * @param int $year год
		 * @return array
		 */
		private function adjustDate($month, $year) {
			$a = [];
			$a[0] = $month;
			$a[1] = $year;

			while ($a[0] > 12) {
				$a[0] -= 12;
				$a[1]++;
			}

			while ($a[0] <= 0) {
				$a[0] += 12;
				$a[1]--;
			}

			return $a;
		}
	}


