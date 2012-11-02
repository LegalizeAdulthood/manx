<?php

interface IDateTimeProvider
{
	/**
	 * @abstract
	 * @return DateTime
	 */
	function now();
}
