<?php

namespace Francisek\PHPUnitTap;

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\TestFailure;
use PHPUnit\Framework\TestListener;
use PHPUnit\Framework\TestSuite;
use PHPUnit\Framework\Warning;
use PHPUnit\Runner\Version;
use PHPUnit\Util\Printer;

class TapPrinter extends Printer implements TestListener
{
    /**
     * @var int $testNumber
     */
    protected $testNumber = 0;

    /**
     * @var int $testSuiteLevel
     */
    protected $testSuiteLevel = 0;

    /**
     * @var bool $testSuccessful
     */
    protected $testSuccessful = true;

    /**
     * @var string $phpunitVersionString
     */
    static private $phpunitVersionString;

    public function __construct($out = null)
    {
        if (null == static::$phpunitVersionString) {
            static::$phpunitVersionString = Version::getVersionString() . "\n";
        }
        parent::__construct($out);
        $this->write("TAP version 13\n");
    }

    /**
     * An error occurred.
     *
     * @param Test $test
     * @param \Exception $e
     * @param float $time
     */
    public function addError(Test $test, \Exception $e, $time)
    {
        $this->writeNotOk($test, 'Error');
    }

    /**
     * A warning occurred.
     *
     * @param Test $test
     * @param Warning $e
     * @param float $time
     */
    public function addWarning(Test $test, Warning $e, $time)
    {
        $this->writeNotOk($test, 'Warning');
    }

    /**
     * A failure occurred.
     *
     * @param Test $test
     * @param AssertionFailedError $e
     * @param float $time
     */
    public function addFailure(Test $test, AssertionFailedError $e, $time)
    {
        $this->writeNotOk($test, 'Failure');
        $message = explode(
            "\n",
            TestFailure::exceptionToString($e)
        );

        $diagnostic = [
            'message' => $message[0],
            'severity' => 'fail'
        ];

        if ($e instanceof ExpectationFailedException) {
            $cf = $e->getComparisonFailure();

            if ($cf !== null) {
                $diagnostic['data'] = [
                    'got' => $cf->getActual(),
                    'expected' => $cf->getExpected()
                ];
            }
        }

        $yaml = new Symfony\Component\Yaml\Dumper();

        $this->write(
            sprintf(
                "  ---\n%s  ...\n",
                $yaml->dump($diagnostic, 2, 2)
            )
        );
    }

    /**
     * Incomplete test.
     *
     * @param Test $test
     * @param \Exception $e
     * @param float $time
     */
    public function addIncompleteTest(Test $test, \Exception $e, $time)
    {
        $this->writeNotOk($test, '', 'TODO Incomplete Test');
    }

    /**
     * Risky test.
     *
     * @param Test $test
     * @param \Exception $e
     * @param float $time
     */
    public function addRiskyTest(Test $test, \Exception $e, $time)
    {
        $this->write(
            sprintf(
                "ok %d - # RISKY%s\n",
                $this->testNumber,
                $e->getMessage() != '' ? ' ' . $e->getMessage() : ''
            )
        );

        $this->testSuccessful = false;
    }

    /**
     * Skipped test.
     *
     * @param Test $test
     * @param \Exception $e
     * @param float $time
     */
    public function addSkippedTest(Test $test, \Exception $e, $time)
    {
        $this->write(
            sprintf(
                "ok %d - # SKIP%s\n",
                $this->testNumber,
                $e->getMessage() != '' ? ' ' . $e->getMessage() : ''
            )
        );

        $this->testSuccessful = false;
    }

    /**
     * A test suite started.
     *
     * @param TestSuite $suite
     */
    public function startTestSuite(TestSuite $suite)
    {
        $this->testSuiteLevel++;
    }

    /**
     * A test suite ended.
     *
     * @param TestSuite $suite
     */
    public function endTestSuite(TestSuite $suite)
    {
        $this->testSuiteLevel--;

        if ($this->testSuiteLevel == 0) {
            $this->write(sprintf("1..%d\n", $this->testNumber));
        }
    }

    /**
     * A test started.
     *
     * @param Test $test
     */
    public function startTest(Test $test)
    {
        $this->testNumber++;
        $this->testSuccessful = true;
    }

    /**
     * A test ended.
     *
     * @param Test $test
     * @param float $time
     */
    public function endTest(Test $test, $time)
    {
        if ($this->testSuccessful === true) {
            $this->write(
                sprintf(
                    "ok %d - %s\n",
                    $this->testNumber,
                    \PHPUnit\Util\Test::describe($test)
                )
            );
        }

        $this->writeDiagnostics($test);
    }

    /**
     * @param Test $test
     * @param string $prefix
     * @param string $directive
     */
    protected function writeNotOk(Test $test, $prefix = '', $directive = '')
    {
        $this->write(
            sprintf(
                "not ok %d - %s%s%s\n",
                $this->testNumber,
                $prefix != '' ? $prefix . ': ' : '',
                \PHPUnit\Util\Test::describe($test),
                $directive != '' ? ' # ' . $directive : ''
            )
        );

        $this->testSuccessful = false;
    }

    /**
     * @param Test $test
     */
    private function writeDiagnostics(Test $test)
    {
        if (!$test instanceof TestCase) {
            return;
        }

        if (!$test->hasOutput()) {
            return;
        }

        foreach (explode("\n", trim($test->getActualOutput())) as $line) {
            $this->write(
                sprintf(
                    "# %s\n",
                    $line
                )
            );
        }
    }

    /**
     * @param string $buffer
     */
    public function write($buffer)
    {
        if ($buffer === static::$phpunitVersionString) {
            $buffer = '# ' . $buffer;
        }
        parent::write($buffer);
    }

}