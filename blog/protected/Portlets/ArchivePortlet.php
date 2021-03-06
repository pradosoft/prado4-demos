<?php
/**
 * ArchivePortlet class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @copyright Copyright &copy; 2006-2016 The PRADO Group
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

Prado::using('Application.Portlets.Portlet');

/**
 * ArchivePortlet class
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @copyright Copyright &copy; 2006-2016 The PRADO Group
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */
class ArchivePortlet extends Portlet
{
	private function makeMonthTime($timestamp)
	{
		$date=getdate($timestamp);
		return mktime(0,0,0,$date['mon'],1,$date['year']);
	}

	public function onLoad($param)
	{
		$currentTime=time();
		$startTime=$this->Application->getModule('data')->queryEarliestPostTime();
		if(empty($startTime))	// if no posts
			$startTime=$currentTime;

		// obtain the timestamp for the initial month
		$date=getdate($startTime);
		$startTime=mktime(0,0,0,$date['mon'],1,$date['year']);

		$date=getdate($currentTime);
		$month=$date['mon'];
		$year=$date['year'];

		$timestamps=array();
		while(true)
		{
			if(($timestamp=mktime(0,0,0,$month,1,$year))<$startTime)
				break;
			$timestamps[]=$timestamp;
			if(--$month===0)
			{
				$month=12;
				$year--;
			}
		}
		$this->MonthList->DataSource=$timestamps;
		$this->MonthList->dataBind();
	}
}

