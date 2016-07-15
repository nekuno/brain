<?php

namespace ApiConsumer\LinkProcessor\UrlParser;

interface UrlParserInterface
{
	public function isUrlValid($url);

	public function cleanURL($url);

	/**
	 * @param $string
	 * @return array
	 */
	public function extractURLsFromText($string);
}