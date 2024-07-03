<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Config;

/**
 * Records time profiling information
 */
class Profile {
	/** @var float */
	public $startTime;

	/** @var float */
	public $endTime;

	private array $timeProfile = [];
	private array $mwProfile = [];
	private array $timeCategories = [];
	private array $counts = [];

	/**
	 * Array of profiles for nested pipelines. So, we effectively end up with
	 * a profile tree with the top-level-doc profile as the root profile.
	 * @var array<Profile>
	 */
	private array $nestedProfiles = [];

	/**
	 * This is the most recently pushed nested profile from a nested pipeline.
	 * @var ?Profile
	 */
	private ?Profile $recentNestedProfile = null;

	public function start(): void {
		$this->startTime = microtime( true );
	}

	public function end(): void {
		$this->endTime = microtime( true );
	}

	public function pushNestedProfile( Profile $p ): void {
		$this->nestedProfiles[] = $this->recentNestedProfile = $p;
	}

	/**
	 * @param array &$profile
	 * @param string $resource
	 * @param float $time Time in milliseconds
	 * @param ?string $cat
	 */
	private function bumpProfileTimeUse(
		array &$profile, string $resource, float $time, ?string $cat
	): void {
		if ( $profile === $this->timeProfile && $this->recentNestedProfile ) {
			// Eliminate double-counting
			$time -= ( $this->recentNestedProfile->endTime - $this->recentNestedProfile->startTime ) * 1000;
			$this->recentNestedProfile = null;
		}

		$profile[$resource] ??= 0;
		$profile[$resource] += $time;
		if ( $cat ) {
			$this->timeCategories[$cat] ??= 0;
			$this->timeCategories[$cat] += $time;
		}
	}

	/**
	 * Update a profile timer.
	 *
	 * @param string $resource
	 * @param float $time Time in milliseconds
	 * @param string|null $cat
	 */
	public function bumpTimeUse( string $resource, float $time, string $cat = null ): void {
		$this->bumpProfileTimeUse( $this->timeProfile, $resource, $time, $cat );
	}

	/**
	 * Update profile usage for "MW API" requests
	 *
	 * @param string $resource
	 * @param float $time Time in milliseconds
	 * @param string|null $cat
	 */
	public function bumpMWTime( string $resource, float $time, string $cat = null ): void {
		// FIXME: For now, skip the category since this leads to double counting
		// when reportind time by categories since this time is part of other
		// '$this->timeProfile' categories already.
		$this->bumpProfileTimeUse( $this->mwProfile, $resource, $time, null );
	}

	/**
	 * Update a profile counter.
	 *
	 * @param string $resource
	 * @param int $n The amount to increment the counter; defaults to 1.
	 */
	public function bumpCount( string $resource, int $n = 1 ): void {
		$this->counts[$resource] ??= 0;
		$this->counts[$resource] += $n;
	}

	/**
	 * @param string $k
	 * @param mixed $v
	 * @param string $comment
	 * @return string
	 */
	private function formatLine( string $k, $v, string $comment = '' ): string {
		$buf = str_pad( $k, 60, " ", STR_PAD_LEFT ) . ':';
		if ( $v === round( $v ) ) {
			$v = (string)$v;
		} else {
			$v = str_pad( (string)( floor( $v * 10000 ) / 10000 ), 5, ' ', STR_PAD_LEFT );
		}
		return $buf . str_pad( $v, 10, " ", STR_PAD_LEFT ) . ( $comment ? ' (' . $comment . ')' : '' );
	}

	/**
	 * Sort comparison function
	 * @param array $a
	 * @param array $b
	 * @return float
	 */
	private static function cmpProfile( array $a, array $b ): float {
		return $b[1] - $a[1];
	}

	private function formatProfile( array $profile, array $options = [] ): array {
		// Sort time profile in descending order

		$total = 0;
		$outLines = [];
		foreach ( $profile as $k => $v ) {
			$total += $v;
			$outLines[] = [ $k, $v ];
		}

		usort( $outLines, [ self::class, 'cmpProfile' ] );

		$lines = [];
		foreach ( $outLines as $line ) {
			$k = $line[0];
			$v = $line[1];
			$lineComment = '';
			if ( isset( $options['printPercentage'] ) ) {
				$lineComment = (string)( round( $v * 1000 / $total ) / 10 ) . '%';
			}

			$buf = $this->formatLine( $k, $v, $lineComment );
			if ( isset( $this->counts[$k] ) ) {
				$buf .= str_pad( '; count: ' .
					str_pad( (string)( $this->counts[$k] ), 6, ' ', STR_PAD_LEFT ),
					6, " ", STR_PAD_LEFT );
				$buf .= str_pad( '; per-instance: ' .
					str_pad(
						(string)( floor( $v * 10000 / $this->counts[$k] ) / 10000 ), 5, ' ', STR_PAD_LEFT
					), 10 );
			}
			$lines[] = $buf;
		}
		return [ 'buf' => implode( "\n", $lines ), 'total' => $total ];
	}

	private function printProfile(): string {
		$outLines = [];
		$mwOut = $this->formatProfile( $this->mwProfile );
		$cpuOut = $this->formatProfile( $this->timeProfile );

		$outLines[] = str_repeat( "-", 85 );
		$outLines[] = "Recorded times (in ms) for various parse components";
		$outLines[] = "";
		$outLines[] = $cpuOut['buf'];
		$outLines[] = str_repeat( "-", 85 );
		$outLines[] = 'Recorded times (in ms) for various "MW API" requests';
		$outLines[] = "";
		$outLines[] = $mwOut['buf'];
		$outLines[] = str_repeat( "-", 85 );
		$parseTime = ( $this->endTime - $this->startTime ) * 1000;
		$outLines[] = $this->formatLine( 'TOTAL PARSE TIME (1)', $parseTime );
		$outLines[] = $this->formatLine( 'TOTAL PARSOID CPU TIME (2)', $cpuOut['total'] );
		if ( $mwOut['total'] > 0 ) {
			$outLines[] = $this->formatLine( 'TOTAL "MW API" TIME', $mwOut['total'] );
		}
		$outLines[] = $this->formatLine( 'Un/over-accounted parse time: (1) - (2)',
			$parseTime - $cpuOut['total'] );
		$outLines[] = "";
		$catOut = $this->formatProfile( $this->timeCategories, [ 'printPercentage' => true ] );
		$outLines[] = $catOut['buf'];
		$outLines[] = "";
		$outLines[] = str_repeat( "-", 85 );

		// dump to stderr via error_log
		return implode( "\n", $outLines );
	}

	private static function swallowArray( array $a, array &$res ): void {
		foreach ( $a as $k => $v ) {
			$res[$k] ??= 0;
			$res[$k] += $v;
		}
	}

	private function reduce( Profile $reducedProfile ): void {
		self::swallowArray( $this->counts, $reducedProfile->counts );
		self::swallowArray( $this->timeCategories, $reducedProfile->timeCategories );
		self::swallowArray( $this->timeProfile, $reducedProfile->timeProfile );
		self::swallowArray( $this->mwProfile, $reducedProfile->mwProfile );

		foreach ( $this->nestedProfiles as $p ) {
			$p->reduce( $reducedProfile );
		}
	}

	private function reduceProfileTree(): Profile {
		$reducedProfile = new Profile();
		$reducedProfile->startTime = $this->startTime;
		$reducedProfile->endTime = $this->endTime;
		$this->reduce( $reducedProfile );
		return $reducedProfile;
	}

	public function print(): string {
		return $this->reduceProfileTree()->printProfile();
	}
}
