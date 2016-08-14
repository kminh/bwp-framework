<?php

/**
 * Copyright (c) 2016 Khang Minh <contact@betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE VERSION 3.0 OR LATER
 */

/**
 * @author Khang Minh <contact@betterwp.net>
 */
class BWP_Framework_AjaxResponder
{
	/**
	 * Response with json data.
	 *
	 * @param array $data
	 */
	public function response_with(array $data)
	{
		@header('Content-Type: application/json');

		echo json_encode($data);

		exit;
	}

	public function fail()
	{
		echo 0; exit;
	}

	public function succeed()
	{
		echo 1; exit;
	}
}
