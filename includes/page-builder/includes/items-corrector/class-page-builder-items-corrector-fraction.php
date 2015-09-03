<?php if (!defined('FW')) die('Forbidden');

class _Page_Builder_Items_Corrector_Fraction
{
	private $n;
	private $d;

	public function get_numerator()
	{
		return $this->n;
	}

	public function get_denominator()
	{
		return $this->d;
	}

	public function set_numerator($numerator)
	{
		return $this->n = $numerator;
	}

	public function set_denominator($denominator)
	{
		// TODO: check for 0
		return $this->d = $denominator;
	}

	public function __construct($numerator, $denominator)
	{
		$this->n = $numerator;
		$this->d = $denominator;
	}

	public function add(_Page_Builder_Items_Corrector_Fraction $fraction)
	{
		$this->n = $this->n * $fraction->get_denominator() + $this->d * $fraction->get_numerator();
		$this->d = $this->d * $fraction->get_denominator();
	}

	public function to_number()
	{
		$this->simplify();
		return $this->n / $this->d;
	}

	private function simplify()
	{
		$gcd = $this->calculate_gcd($this->n, $this->d);
		$this->n /= $gcd;
		$this->d /= $gcd;
	}

	private function calculate_gcd($a, $b)
	{
		if ($b === 0) return $a;
		else return self::calculate_gcd($b, $a % $b);
	}
}