<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Tests\Unit\Sync\Notification\Helper;

use Mautic\IntegrationsBundle\Sync\Notification\Helper\OwnerProvider;
use Mautic\IntegrationsBundle\Sync\Notification\Helper\RouteHelper;
use Mautic\IntegrationsBundle\Sync\Notification\Helper\UserHelper;
use Mautic\IntegrationsBundle\Sync\Notification\Helper\UserNotificationBuilder;
use Mautic\IntegrationsBundle\Sync\Notification\Helper\UserNotificationHelper;
use Mautic\IntegrationsBundle\Sync\Notification\Writer;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Contact;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserNotificationHelperTest extends TestCase
{
    /**
     * @var Writer|MockObject
     */
    private \PHPUnit\Framework\MockObject\MockObject $writer;

    /**
     * @var UserHelper|MockObject
     */
    private \PHPUnit\Framework\MockObject\MockObject $userHelper;

    /**
     * @var OwnerProvider|MockObject
     */
    private \PHPUnit\Framework\MockObject\MockObject $ownerProvider;

    /**
     * @var RouteHelper|MockObject
     */
    private \PHPUnit\Framework\MockObject\MockObject $routeHelper;

    /**
     * @var TranslatorInterface|MockObject
     */
    private \PHPUnit\Framework\MockObject\MockObject $translator;

    private \Mautic\IntegrationsBundle\Sync\Notification\Helper\UserNotificationHelper $helper;

    protected function setUp(): void
    {
        $this->writer        = $this->createMock(Writer::class);
        $this->userHelper    = $this->createMock(UserHelper::class);
        $this->ownerProvider = $this->createMock(OwnerProvider::class);
        $this->routeHelper   = $this->createMock(RouteHelper::class);
        $this->translator    = $this->createMock(TranslatorInterface::class);

        $userNotificationBuilder = new UserNotificationBuilder($this->userHelper,
            $this->ownerProvider,
            $this->routeHelper,
            $this->translator
        );
        $this->helper = new UserNotificationHelper($this->writer, $userNotificationBuilder);
    }

    public function testNotificationSentToOwner(): void
    {
        $this->ownerProvider->expects($this->once())
            ->method('getOwnersForObjectIds')
            ->with(Contact::NAME, [1])
            ->willReturn([['owner_id' => 1, 'id' => 1]]);

        $this->userHelper->expects($this->never())
            ->method('getAdminUsers');

        $this->translator->expects($this->exactly(2))
            ->method('trans')
            ->withConsecutive(
                ['mautic.integration.sync.user_notification.header', $this->anything()],
                ['mautic.integration.sync.user_notification.sync_error', $this->anything()]
            )
            ->willReturn('test');

        $this->writer->expects($this->once())
            ->method('writeUserNotification');

        $this->routeHelper->expects($this->once())
            ->method('getLink');

        $this->helper->writeNotification('test', 'test', 'test', Contact::NAME, 1, 'foobar');
    }

    public function testNotificationSentToAdmins(): void
    {
        $this->ownerProvider->expects($this->once())
            ->method('getOwnersForObjectIds')
            ->with(Contact::NAME, [1])
            ->willReturn([]);

        $this->userHelper->expects($this->once())
            ->method('getAdminUsers')
            ->willReturn([1]);

        $this->translator->expects($this->exactly(2))
            ->method('trans')
            ->withConsecutive(
                ['mautic.integration.sync.user_notification.header', $this->anything()],
                ['mautic.integration.sync.user_notification.sync_error', $this->anything()]
            )
            ->willReturn('test');

        $this->writer->expects($this->once())
            ->method('writeUserNotification');

        $this->routeHelper->expects($this->once())
            ->method('getLink');

        $this->helper->writeNotification('test', 'test', 'test', Contact::NAME, 1, 'foobar');
    }
}
