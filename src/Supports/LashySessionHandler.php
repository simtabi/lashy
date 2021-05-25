<?php

namespace Simtabi\Lashy\Supports;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Session\DatabaseSessionHandler as DSH;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\Request;
use Simtabi\Lashy\Contracts\LashyGuard;

class LashySessionHandler extends DSH
{


    /**
     * Create a new database session handler instance.
     *
     * @param ConnectionInterface $connection
     * @param  string  $table
     * @param  int  $minutes
     * @param Container|null  $container
     * @return void
     */
    final public function __construct(ConnectionInterface $connection, $table, $minutes, Container $container = null)
    {
        parent::__construct($connection, $table, $minutes, $container);
    }

    private function userType()
    {
        return trim(request()->lashyAuthUserType ?? 'unknown');
    }

    /**
     * {@inheritdoc}
     */
    final public function read($sessionId)
    {
        $session = (object) $this->getQuery()
            ->where('user_type', $this->userType())
            ->find($sessionId);

        if ($this->expired($session)) {
            $this->exists = true;

            return '';
        }

        if (isset($session->payload)) {
            $this->exists = true;

            return base64_decode($session->payload);
        }

        return '';
    }

    /**
     * Perform an update operation on the session ID.
     *
     * @param  string  $sessionId
     * @param  string  $payload
     * @return int
     */
    final protected function performUpdate($sessionId, $payload)
    {
        return $this->getQuery()
            ->where('id', $sessionId)
            ->where('user_type', $this->userType())
            ->update($payload);
    }

    /**
     * Get the default payload for the session.
     *
     * @param string $data
     * @return array
     * @throws BindingResolutionException
     */
    final protected function getDefaultPayload($data)
    {
        $payload = [
            'user_type'     => $this->userType(),
            'payload'       => base64_encode($data),
            'last_activity' => $this->currentTime(),
        ];

        if (! $this->container) {
            return $payload;
        }

        return tap($payload, function (&$payload) {
            $this->addUserInformation($payload)
                ->addRequestInformation($payload);
        });
    }

    /**
     * Get the currently authenticated user's ID.
     *
     * @return mixed
     * @throws BindingResolutionException
     */
    final protected function userId()
    {
        return $this->container->make(LashyGuard::class)->id();
    }

    /**
     * Get the IP address for the current request.
     *
     * @return string
     * @throws BindingResolutionException
     */
    final protected function ipAddress()
    {
        return $this->container->make('request')->ip();
    }

    /**
     * {@inheritdoc}
     */
    final public function destroy($sessionId)
    {
        $this->getQuery()
            ->where('id', $sessionId)
            ->where('user_type', $this->userType())
            ->delete();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function gc($lifetime)
    {
        $this->getQuery()
            ->where('last_activity', '<=', $this->currentTime() - $lifetime)
            ->where('user_type', $this->userType())
            ->delete();
    }

}
