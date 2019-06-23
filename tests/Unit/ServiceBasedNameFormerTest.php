<?php

namespace Tests\Unit;

use Mitchdav\SNS\ServiceBasedNameFormer;
use Tests\TestCase;

class ServiceBasedNameFormerTest extends TestCase
{
	/** @test */
	public function forms_correctly_with_configured_name()
	{
		$nameFormer = new ServiceBasedNameFormer();

		$name = 'this_is-my@name';

		$this->assertEquals($name, $nameFormer->formName('service', 'label', [
			'name' => $name,
		]));
	}

	/** @test */
	public function forms_correctly_with_configured_label()
	{
		$nameFormer = new ServiceBasedNameFormer();

		$label = 'this_is-my@label';

		$this->assertEquals($label, $nameFormer->formName('service', 'label', [
			'label' => $label,
		]));
	}

	/** @test */
	public function forms_correctly_with_label()
	{
		$nameFormer = new ServiceBasedNameFormer();

		$label = 'this_is-my@label';
		$name  = 'this_is-my';

		$this->assertEquals($name, $nameFormer->formName('service', $label, []));
	}

	/** @test */
	public function forms_correctly_with_static_prefix()
	{
		$nameFormer = new ServiceBasedNameFormer();

		$this->assertEquals('service_label', $nameFormer->formName('service', 'label', [
			'prefix' => 'service',
			'joiner' => '_',
		]));
	}

	/** @test */
	public function forms_correctly_with_different_joiner()
	{
		$nameFormer = new ServiceBasedNameFormer();

		$this->assertEquals('serviceabclabel', $nameFormer->formName('service', 'label', [
			'prefix' => 'service',
			'joiner' => 'abc',
		]));
	}

	/** @test */
	public function forms_correctly_with_service_prefix()
	{
		$nameFormer = new ServiceBasedNameFormer();

		$this->assertEquals('my-service_my-label', $nameFormer->formName('my-service', 'my-label', [
			'prefix' => ServiceBasedNameFormer::PREFIX_SERVICE_NAME,
			'joiner' => '_',
		]));
	}

	/** @test */
	public function forms_correctly_with_additional_service_prefix()
	{
		$nameFormer = new ServiceBasedNameFormer();

		$this->assertEquals('prod-my-service_my-label', $nameFormer->formName('my-service', 'my-label', [
			'prefix' => 'prod-' . ServiceBasedNameFormer::PREFIX_SERVICE_NAME,
			'joiner' => '_',
		]));
	}

	/** @test */
	public function removes_at_suffix_from_label()
	{
		$nameFormer = new ServiceBasedNameFormer();

		$this->assertEquals('service_label', $nameFormer->formName('service', 'label@region-1', [
			'prefix' => ServiceBasedNameFormer::PREFIX_SERVICE_NAME,
			'joiner' => '_',
		]));
	}

	/** @test */
	public function ignores_prefix_if_joiner_missing()
	{
		$nameFormer = new ServiceBasedNameFormer();

		$this->assertEquals('label', $nameFormer->formName('service', 'label', [
			'prefix' => ServiceBasedNameFormer::PREFIX_SERVICE_NAME,
		]));
	}

	/** @test */
	public function ignores_joiner_if_prefix_missing()
	{
		$nameFormer = new ServiceBasedNameFormer();

		$this->assertEquals('label', $nameFormer->formName('service', 'label', [
			'joiner' => '_',
		]));
	}
}
