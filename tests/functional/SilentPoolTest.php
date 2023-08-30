<?php

namespace WpOop\TransientCache\Tests\Func;

use Brain\Monkey\Expectation\Exception\ExpectationArgsRequired;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use wpdb;
use WpOop\TransientCache\CachePool;
use WpOop\TransientCache\SilentPool as TestSubject;
use PHPUnit\Framework\TestCase;
use Brain\Monkey\Functions;
use function Brain\Monkey\tearDown;

class SilentPoolTest extends TestCase
{
    protected function tearDown(): void
    {
        tearDown();
        parent::tearDown();
    }

    /**
     * @param wpdb   $wpdb
     * @param string $poolName
     * @param        $defaultValue
     * @param null   $defaultTtl
     *
     * @return TestSubject&MockObject
     */
    public function createCache(wpdb $wpdb, string $poolName, $defaultValue, $defaultTtl = null): CacheInterface
    {
        $mock = $this->getMockBuilder(CachePool::class)
            ->setMethods(null)
            ->setConstructorArgs([$wpdb, $poolName, $defaultValue, $defaultTtl])
            ->getMock();

        return $mock;
    }

    /**
     * @return wpdb&MockObject
     */
    public function createWpdb(): wpdb
    {
        require_once(ROOT_DIR . '/vendor/johnpbloch/wordpress-core/wp-includes/wp-db.php');
        $mock = $this->getMockBuilder(wpdb::class)
            ->setMethods(['get_col', 'query', '_real_escape', 'get_results'])
            ->disableOriginalConstructor()
            ->getMock();

        return $mock;
    }

    /**
     * @param int $length
     *
     * @return string
     */
    public function generateRandomString(int $length): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        return $randomString;
    }

    /**
     * @param CacheInterface $cache
     *
     * @return TestSubject&MockObject
     */
    public function createInstance(CacheInterface $cache): TestSubject
    {
        $mock = $this->getMockBuilder(TestSubject::class)
            ->setMethods(null)
            ->setConstructorArgs([$cache])
            ->getMock();

        return $mock;
    }

    /**
     * @param null $defaultValue The default value that the instance will use internally for false-negative checks.
     *
     * @return TestSubject&MockObject
     */
    public function createConfiguredInstance($defaultValue = null): TestSubject
    {
        $poolName = $this->generateRandomString(20);

        $wpdb = $this->createWpdb();
        $tablePrefix = uniqid();
        $sep = CachePool::NAMESPACE_SEPARATOR;
        $pref = '_transient_';
        $options = [
            "{$pref}{$poolName}{$sep}" . uniqid('key1') => uniqid('val1'),
            "{$pref}{$poolName}{$sep}" . uniqid('key2') => uniqid('val2'),
            "{$pref}{$poolName}{$sep}" . uniqid('key3') => uniqid('val3'),
        ];
        $wpdb->expects($this->any())
            ->method('get_results')
            ->with("SELECT `option_name` FROM `{$tablePrefix}options` WHERE `option_name` LIKE '_transient_$poolName/%'")
            ->will($this->returnValue($options));

        $defaultValue = $defaultValue ?? $this->generateRandomString(25);
        $defaultTtl = rand(1, 99999);

        $cache = $this->createCache($wpdb, $poolName, $defaultValue, $defaultTtl);
        $subject = $this->createInstance($cache);

        return $subject;
    }

    /**
     * Tests that the subject returns a false value on error.
     *
     * @throws ExpectationArgsRequired
     * @throws InvalidArgumentException
     */
    public function testDelete()
    {
        {
            $subject = $this->createConfiguredInstance();
        }
        {
            Functions\expect('delete_transient')
                ->with($this->anything())
                ->andThrow(new Exception('Random exception'));
        }
        {
            $result = $subject->delete(uniqid('key'));
            $this->assertFalse($result);
        }
    }

    /**
     * Tests that the subject returns a false value on error.
     *
     * @throws ExpectationArgsRequired
     */
    public function testClear()
    {
        {
            $subject = $this->createConfiguredInstance();
        }
        {
            Functions\expect('delete_transient')
                ->with($this->anything())
                ->andThrow(new Exception('Random exception'));
        }
        {
            $result = $subject->clear();
            $this->assertFalse($result);
        }
    }

    /**
     * Tests that the subject returns an empty map on error.
     *
     * @throws ExpectationArgsRequired
     * @throws InvalidArgumentException
     */
    public function testGetMultiple()
    {
        {
            $defaultValue = uniqid('err');
            $default = uniqid('default');
            $key = uniqid('key');
            $subject = $this->createConfiguredInstance($defaultValue);
        }
        {
            Functions\expect('get_transient')
                ->with($this->anything())
                ->andThrow(new Exception('Random Exception'));
        }
        {
            $result = $subject->getMultiple([$key], $default);
            $this->assertEquals([], $result);
        }
    }

    /**
     * Tests that the subject returns a false value on error.
     *
     * @throws ExpectationArgsRequired
     * @throws InvalidArgumentException
     */
    public function testSetMultiple()
    {
        {
            $subject = $this->createConfiguredInstance();
        }
        {
            Functions\expect('set_transient')
                ->with($this->anything())
                ->andThrow(new Exception('Random Exception'));
        }
        {
            $result = $subject->setMultiple([uniqid('key') => uniqid('value')]);
            $this->assertFalse($result);
        }
    }

    /**
     * Tests that the subject returns a false value on error.
     *
     * @throws ExpectationArgsRequired
     * @throws InvalidArgumentException
     */
    public function testSet()
    {
        {
            $subject = $this->createConfiguredInstance();
        }
        {
            Functions\expect('set_transient')
                ->with($this->anything())
                ->andThrow(new Exception('Random Exception'));
        }
        {
            $result = $subject->set(uniqid('key'), uniqid('value'));
            $this->assertFalse($result);
        }
    }

    /**
     * Tests that the subject returns the default value on error.
     *
     * @throws ExpectationArgsRequired
     * @throws InvalidArgumentException
     */
    public function testGet()
    {
        {
            $default = uniqid('default');
            $subject = $this->createConfiguredInstance();
        }
        {
            Functions\expect('get_transient')
                ->with($this->anything())
                ->andThrow(new Exception('Random Exception'));
        }
        {
            $result = $subject->get(uniqid('key'), $default);
            $this->assertEquals($default, $result);
        }
    }

    /**
     * Tests that the subject returns a false value on error.
     *
     * @throws ExpectationArgsRequired
     * @throws InvalidArgumentException
     */
    public function testHas()
    {
        {
            $subject = $this->createConfiguredInstance();
        }
        {
            Functions\expect('get_transient')
                ->with($this->anything())
                ->andThrow(new Exception('Random Exception'));
        }
        {
            $result = $subject->has(uniqid('key'));
            $this->assertFalse($result);
        }
    }

    /**
     * Tests that the subject returns a false value on error.
     *
     * @throws ExpectationArgsRequired
     * @throws InvalidArgumentException
     */
    public function testDeleteMultiple()
    {
        {
            $subject = $this->createConfiguredInstance();
        }
        {
            Functions\expect('delete_transient')
                ->with($this->anything())
                ->andThrow(new Exception('Random Exception'));
        }
        {
            $result = $subject->deleteMultiple([uniqid('key1'), uniqid('key2')]);
            $this->assertFalse($result);
        }
    }
}
