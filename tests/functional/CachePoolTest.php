<?php

namespace WpOop\TransientCache\Tests\Func;

use DateInterval;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\SimpleCache\CacheException;
use Psr\SimpleCache\InvalidArgumentException;
use wpdb;
use WpOop\TransientCache\CachePool as TestSubject;
use PHPUnit\Framework\TestCase;
use Brain\Monkey\Functions;
use function Brain\Monkey\tearDown;

class CachePoolTest extends TestCase
{
    protected const MAX_KEY_LENGTH = 64;
    protected const MAX_POOL_NAME_LENGTH = 107;

    protected function tearDown(): void
    {
        tearDown();
        parent::tearDown();
    }

    /**
     * @return TestSubject&MockObject
     */
    public function createInstance(wpdb $wpdb, string $poolName, $defaultValue, $defaultTtl = null): TestSubject
    {
        $mock = $this->getMockBuilder(TestSubject::class)
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
     * Tests that the subject can correctly determine and clear all of its keys.
     *
     * @throws \Brain\Monkey\Expectation\Exception\ExpectationArgsRequired
     * @throws \WpOop\TransientCache\Exception\CacheException
     */
    public function testClear()
    {
        {
            $poolName = uniqid('pool');
            $tablePrefix = 'wp_';
            $wpdb = $this->createWpdb();
            $wpdb->prefix = $tablePrefix;
            $key = uniqid('key');
            $keys = [
                uniqid('key1'),
                uniqid('key2'),
                uniqid('key3'),
            ];
            $subject = $this->createInstance($wpdb, $poolName, uniqid('default'));
        }

        {
            $options = array_map(function ($value) use ($poolName) {
                return ['option_name' => "_transient_$poolName/$value"];
            }, $keys);
            $wpdb->expects($this->exactly(1))
                ->method('get_results')
                ->with("SELECT `option_name` FROM `{$tablePrefix}options` WHERE `option_name` LIKE '_transient_$poolName/%'", ARRAY_A)
                ->will($this->returnValue($options));

            Functions\expect('has_filter')
                ->andReturn(true);

            Functions\expect('delete_transient')
                ->with($options[0], $options[1], $options[2])
                ->andReturn(true);
        }

        {
            $subject->clear();
        }
    }

    /**
     * Tests that a single key can be requested from cache successfully if found.
     *
     * @throws \Brain\Monkey\Expectation\Exception\ExpectationArgsRequired
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function testGet()
    {
        {
            $poolName = uniqid('pool');
            $defaultValue = uniqid('default');
            $wpdb = $this->createWpdb();
            $key = uniqid('key');
            $value = uniqid('value');
            $separator = TestSubject::NAMESPACE_SEPARATOR;
            $transientName = "{$poolName}{$separator}{$key}";
            $optionName = "_transient_$transientName";
            $subject = $this->createInstance($wpdb, $poolName, $defaultValue);
        }

        {
            Functions\expect('get_option')
                ->with($optionName)
                ->andReturn($value);

            Functions\expect('get_transient')
                ->with($transientName)
                ->andReturn($value);
        }

        {
            $result = $subject->get($key);
            $this->assertEquals($value, $result);
        }
    }

    /**
     * Tests that the correct exception is thrown if the key contains invalid chars.
     *
     * @throws InvalidArgumentException
     */
    public function testGetInvalidKey()
    {
        {
            $wpdb = $this->createWpdb();
            $key = 'my/key';
            $pool = $this->createInstance($wpdb, uniqid('pool'), uniqid('default'));
        }

        {
            $this->expectException(InvalidArgumentException::class);
            $pool->get($key);
        }
    }

    /**
     * Tests that the correct exception is thrown when a delete operation fails.
     *
     * @throws InvalidArgumentException
     */
    public function testDeleteError()
    {
        {
            $wpdb = $this->createWpdb();
            $key = uniqid('key');
            $pool = $this->createInstance($wpdb, uniqid('pool'), uniqid('default'));
        }

        {
            Functions\expect('delete_transient')
                ->andReturn(false);
        }

        {
            $this->expectException(CacheException::class);
            $pool->delete($key);
        }
    }

    /**
     * Tests that a default value is returned if a key is not found.
     *
     * @throws \Brain\Monkey\Expectation\Exception\ExpectationArgsRequired
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function testGetDefault()
    {
        {
            $poolName = uniqid('pool');
            $defaultValue = uniqid('default');
            $wpdb = $this->createWpdb();
            $key = uniqid('key');
            $default = uniqid('myval');
            $separator = TestSubject::NAMESPACE_SEPARATOR;
            $transientName = "{$poolName}{$separator}{$key}";
            $optionName = "_transient_$transientName";
            $subject = $this->createInstance($wpdb, $poolName, $defaultValue);
        }

        {
            Functions\expect('get_transient')
                ->with($transientName)
                ->andReturn(false);
            Functions\expect('get_option')
                ->with($optionName)
                ->andReturn($defaultValue);
        }

        {
            $result = $subject->get($key, $default);
            $this->assertEquals($default, $result);
        }
    }

    /**
     * @doesNotPerformAssertions
     * @throws \Brain\Monkey\Expectation\Exception\ExpectationArgsRequired
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function testSet()
    {
        {
            $poolName = uniqid('pool');
            $wpdb = $this->createWpdb();
            $key = uniqid('key');
            $value = uniqid('value');
            $ttl = new DateInterval('P1D');
            $separator = TestSubject::NAMESPACE_SEPARATOR;
            $transientName = "{$poolName}{$separator}{$key}";
            $subject = $this->createInstance($wpdb, $poolName, uniqid('default'));
        }

        {
            Functions\expect('set_transient')
                ->with($transientName, $value, 60 * 60 * 24)
                ->andReturn(true);
        }

        {
            $subject->set($key, $value, $ttl);
        }
    }

    /**
     * @doesNotPerformAssertions
     * @throws \Brain\Monkey\Expectation\Exception\ExpectationArgsRequired
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function testSetDefaultTtl()
    {
        {
            $poolName = uniqid('pool');
            $wpdb = $this->createWpdb();
            $key = uniqid('key');
            $value = uniqid('value');
            $defaultTtl = new DateInterval('P2D');
            $separator = TestSubject::NAMESPACE_SEPARATOR;
            $transientName = "{$poolName}{$separator}{$key}";
            $subject = $this->createInstance($wpdb, $poolName, uniqid('default'), $defaultTtl);
        }

        {
            Functions\expect('set_transient')
                ->with($transientName, $value, 2 * 60 * 60 * 24)
                ->andReturn(true);
        }

        {
            $subject->set($key, $value, null);
        }
    }

    /**
     * Tests that a correct exception is thrown when key too long.
     *
     * @throws \Brain\Monkey\Expectation\Exception\ExpectationArgsRequired
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function testSetKeyTooLong()
    {
        {
            $poolName = $this->generateRandomString(static::MAX_POOL_NAME_LENGTH + 1);
            $wpdb = $this->createWpdb();
            $key = $this->generateRandomString(static::MAX_KEY_LENGTH);
            $value = uniqid('value');
            $ttl = rand(1, 9999);
            $separator = TestSubject::NAMESPACE_SEPARATOR;
            $subject = $this->createInstance($wpdb, $poolName, uniqid('default'));
        }

        {
            $this->expectException(InvalidArgumentException::class);
            $subject->set($key, $value, $ttl);
        }
    }

    /**
     * @doesNotPerformAssertions
     * @throws \Brain\Monkey\Expectation\Exception\ExpectationArgsRequired
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function testDelete()
    {
        {
            $poolName = uniqid('pool');
            $wpdb = $this->createWpdb();
            $key = uniqid('key');
            $separator = TestSubject::NAMESPACE_SEPARATOR;
            $transientName = "{$poolName}{$separator}{$key}";
            $subject = $this->createInstance($wpdb, $poolName, uniqid('default'));
        }

        {
            Functions\expect('delete_transient')
                ->with($transientName)
                ->andReturn(true);
        }

        {
            $subject->delete($key);
        }
    }

    /**
     * @throws \Brain\Monkey\Expectation\Exception\ExpectationArgsRequired
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function testHas()
    {
        {
            $poolName = uniqid('pool');
            $defaultValue = uniqid('default');
            $wpdb = $this->createWpdb();
            $key = uniqid('key');
            $notThereKey = uniqid('not-there');
            $subject = $this->createInstance($wpdb, $poolName, $defaultValue);
        }

        {
            Functions\expect('get_transient')
                ->andReturnValues([uniqid('value'), false]);

            Functions\expect('get_option')
                ->andReturnValues([$defaultValue]);
        }

        {
            $result = $subject->has($key);
            $this->assertTrue($result);
            $this->assertFalse($subject->has($notThereKey));
        }
    }

    /**
     * Imitates a scenario where `set_transient()` erroneously returns `false` if set value is same as existing.
     */
    public function testWritingSameValueSucceeds()
    {
        {
            $poolName = uniqid('pool');
            $defaultValue = uniqid('default');
            $wpdb = $this->createWpdb();
            $key = uniqid('key');
            $value = uniqid('value');
            $subject = $this->createInstance($wpdb, $poolName, $defaultValue);
        }

        {
            Functions\expect('get_transient')
                ->andReturn($value);
            Functions\expect('set_transient')
                ->andReturn();
        }

        {
            // If no exception - success
            $subject->set($key, $value, rand(1, 99999));

            // Problem setting a different value results in an exception
            $this->expectException(CacheException::class);
            $subject->set($key, uniqid('other-value'), rand(1, 99999));
        }
    }
}
