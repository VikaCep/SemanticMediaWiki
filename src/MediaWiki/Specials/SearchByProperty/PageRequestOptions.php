<?php

namespace SMW\MediaWiki\Specials\SearchByProperty;

use SMW\DataValueFactory;
use SMW\DataValues\TelephoneUriValue;
use SMWUriValue as UriValue;
use SMW\UrlEncoder;
use SMWNumberValue as NumberValue;
use SMWPropertyValue as PropertyValue;
use SMWStringValue as TextValue;

/**
 * @license GNU GPL v2+
 * @since   2.1
 *
 * @author mwjames
 */
class PageRequestOptions {

	/**
	 * @var string
	 */
	private $queryString;

	/**
	 * @var array
	 */
	private $requestOptions;

	/**
	 * @var UrlEncoder
	 */
	private $urlEncoder;

	/**
	 * @var PropertyValue
	 */
	public $property;

	/**
	 * @var string
	 */
	public $propertyString;

	/**
	 * @var string
	 */
	public $valueString;

	/**
	 * @var DataValue
	 */
	public $value;

	/**
	 * @var integer
	 */
	public $limit = 20;

	/**
	 * @var integer
	 */
	public $offset = 0;

	/**
	 * @var boolean
	 */
	public $nearbySearch = false;

	/**
	 * @since 2.1
	 *
	 * @param string $queryString
	 * @param array $requestOptions
	 */
	public function __construct( $queryString, array $requestOptions ) {
		$this->queryString = $queryString;
		$this->requestOptions = $requestOptions;
		$this->urlEncoder = new UrlEncoder();
	}

	/**
	 * @since 2.1
	 */
	public function initialize() {

		$params = explode( '/', $this->queryString );
		reset( $params );

		// Remove empty elements
		$params = array_filter( $params, 'strlen' );

		$property = isset( $this->requestOptions['property'] ) ? $this->requestOptions['property'] : current( $params );
		$value = isset( $this->requestOptions['value'] ) ? $this->requestOptions['value'] : next( $params );

		$property = $this->urlEncoder->unescape(
			str_replace( array( '_' ), array( ' ' ), $property )
		);

		$value = str_replace(
			array( '-25', '-2D' ),
			array( '%', '-' ),
			$value
		);

		$this->property = PropertyValue::makeUserProperty( $property );

		if ( !$this->property->isValid() ) {
			$this->propertyString = $property;
			$this->value = null;
			$this->valueString = $value;
		} else {
			$this->propertyString = $this->property->getDataItem()->getLabel();
			$this->setValue( $value );
		}

		$this->setLimit();
		$this->setOffset();
		$this->setNearbySearch();
	}

	private function setValue( $value ) {

		$this->value = DataValueFactory::getInstance()->newDataValueByProperty(
			$this->property->getDataItem()
		);

		if ( $this->value instanceof NumberValue ) {
			$value = str_replace( array( '-20' ), array( ' ' ), $value );
			// Do not try to decode things like 1.2e-13
			// Signals that we don't want any precision limitation
			$this->value->setOption( 'no.displayprecision', true );
		} elseif ( $this->value instanceof TelephoneUriValue ) {
			// No encoding to avoid turning +1-201-555-0123
			// into +1 1U523 or further obfuscate %2B1-2D201-2D555-2D0123 ...
		} elseif ( $this->value instanceof TextValue || $this->value instanceof UriValue ) {
			$value = $this->urlEncoder->unescape( $value );
		} else {
			$value = $this->urlEncoder->unescape( $value );
		}

		$this->value->setUserValue( $value );

		$this->valueString = $this->value->isValid() ? $this->value->getWikiValue() : $value;
	}

	private function setLimit() {
		if ( isset( $this->requestOptions['limit'] ) ) {
			$this->limit = intval( $this->requestOptions['limit'] );
		}
	}

	private function setOffset() {
		if ( isset( $this->requestOptions['offset'] ) ) {
			$this->offset = intval( $this->requestOptions['offset'] );
		}
	}

	private function setNearbySearch() {

		if ( $this->value === null ) {
			return null;
		}

		if ( isset( $this->requestOptions['nearbySearchForType'] ) && is_array( $this->requestOptions['nearbySearchForType'] ) ) {
			$this->nearbySearch = in_array( $this->value->getTypeID(), $this->requestOptions['nearbySearchForType'] );
		}
	}

}
