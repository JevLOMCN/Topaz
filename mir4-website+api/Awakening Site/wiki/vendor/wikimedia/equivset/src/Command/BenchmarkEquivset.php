<?php
/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 */

namespace Wikimedia\Equivset\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Wikimedia\Equivset\Equivset;

/**
 * Benchmark Equivset Command.
 * @codeCoverageIgnore
 */
class BenchmarkEquivset extends Command {
	/**
	 * {@inheritdoc}
	 */
	protected function configure() {
		$this->setName( 'benchmark-equivset' );
		$this->setDescription(
			'Benchmark the JSON, PHP, serialized, and plain text versions of the equivset in `./dist`'
		);
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param InputInterface $input Input.
	 * @param OutputInterface $output Output.
	 *
	 * @return int Return status.
	 */
	public function execute( InputInterface $input, OutputInterface $output ) {
		$output->writeln( "Benchmark load time from different sources" );

		foreach ( [
			'equivset.ser',
			'equivset.php',
		] as $filename ) {
			$file = __DIR__ . '/../../dist/' . $filename;
			// warmup
			for ( $i = 0; $i < 10; $i++ ) {
				$equivset = new Equivset( [], $file );
				$equivset->all();
			}

			// bench
			$t = microtime( true );
			for ( $i = 0; $i < 1000; $i++ ) {
				$equivset = new Equivset( [], $file );
				$equivset->all();
			}
			$t = ( microtime( true ) - $t ) * 1000;
			$output->writeln( $filename . ': ' . $t . ' ms' );
		}

		return 0;
	}
}
