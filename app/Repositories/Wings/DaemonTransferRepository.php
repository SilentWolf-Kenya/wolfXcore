<?php

namespace Pterodactyl\Repositories\Wings;

use Pterodactyl\Models\Node;
use Lcobucci\JWT\Token\Plain;
use GuzzleHttp\Exception\GuzzleException;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;

/**
 * @method \wolfXcore\Repositories\Wings\DaemonTransferRepository setNode(\wolfXcore\Models\Node $node)
 * @method \wolfXcore\Repositories\Wings\DaemonTransferRepository setServer(\wolfXcore\Models\Server $server)
 */
class DaemonTransferRepository extends DaemonRepository
{
    /**
     * @throws DaemonConnectionException
     */
    public function notify(Node $targetNode, Plain $token): void
    {
        try {
            $this->getHttpClient()->post(sprintf('/api/servers/%s/transfer', $this->server->uuid), [
                'json' => [
                    'server_id' => $this->server->uuid,
                    'url' => $targetNode->getConnectionAddress() . '/api/transfers',
                    'token' => 'Bearer ' . $token->toString(),
                    'server' => [
                        'uuid' => $this->server->uuid,
                        'start_on_completion' => false,
                    ],
                ],
            ]);
        } catch (GuzzleException $exception) {
            throw new DaemonConnectionException($exception);
        }
    }
}
