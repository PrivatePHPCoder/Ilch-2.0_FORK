<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

namespace Modules\Fitness\Controllers;

use Ilch\Controller\Frontend;
use Modules\Fitness\Mappers\Milestone as MilestoneMapper;
use Modules\Fitness\Models\Milestone as MilestoneModel;
use Modules\Fitness\Service\Access;
use Modules\Fitness\Service\Milestones;
use Modules\Fitness\Service\Trainings;
use Modules\User\Mappers\Notifications as NotificationsMapper;
use Modules\User\Models\Notification as NotificationModel;

/**
 * Common base for all frontend controllers of the fitness module.
 *
 * Controllers that define their own init() have to call parent::init().
 */
class Base extends Frontend
{
    /**
     * Group id of guests.
     */
    private const GROUP_GUEST = 3;

    /**
     * Session key for milestones that were just reached.
     */
    private const SESSION_REACHED_MILESTONES = 'fitness_reachedMilestones';

    /**
     * Session key for the visitor view of managers.
     */
    private const SESSION_VISITOR_VIEW = 'fitness_visitorView';

    public function init()
    {
        $this->useFitnessLayout();
        $this->getView()->set('visitorView', $this->isVisitorView());

        $this->getLayout()->header()
            ->css(self::withVersion('static/css/fitness.css'))
            ->js(self::withVersion('static/js/fitness.js'));
    }

    /**
     * Adds the time of the last change to the path of a static file. Browsers then load the file
     * again after an update instead of using an old copy from their cache.
     *
     * @param string $path path inside the module
     * @return string
     */
    private static function withVersion(string $path): string
    {
        $file = APPLICATION_PATH . '/modules/fitness/' . $path;

        return is_file($file) ? $path . '?v=' . filemtime($file) : $path;
    }

    /**
     * Shows the page in the layout file of this module, if that is turned on in the settings.
     *
     * Only the default file of the active layout gets replaced. A file chosen by the maintenance
     * mode or by a layout route of the active layout (for example
     * 'layouts' => ['fitness' => [['module' => 'fitness']]]) stays untouched.
     */
    protected function useFitnessLayout(): void
    {
        $layout = $this->getLayout();
        $layoutKey = $layout->getLayoutKey();

        if ($this->getConfig()->get('fitness_ownLayout') && $layout->getFile() === 'layouts/' . $layoutKey . '/index') {
            $layout->setFile('modules/fitness/layouts/fitness', $layoutKey);
        }
    }

    /**
     * Returns the group ids of the current visitor. Guests belong to the guest group.
     *
     * @return int[]
     */
    protected function getVisitorGroupIds(): array
    {
        $user = $this->getUser();

        return $user ? array_map('intval', array_keys($user->getGroups())) : [self::GROUP_GUEST];
    }

    /**
     * Returns whether the visitor may manage the fitness module and sees the admin preview. Such
     * visitors may see unpublished programs, inactive exercises and the content of all programs.
     * Managers who switched to the visitor view get false.
     *
     * @return bool
     */
    protected function canManageFitness(): bool
    {
        return Access::canManage($this->getUser()) && !$this->isVisitorView();
    }

    /**
     * Whether a manager has switched off the admin preview to see the area like a visitor.
     *
     * @return bool
     */
    protected function isVisitorView(): bool
    {
        return !empty($_SESSION[self::SESSION_VISITOR_VIEW]) && Access::canManage($this->getUser());
    }

    /**
     * @param bool $visitorView
     */
    protected function setVisitorView(bool $visitorView): void
    {
        if ($visitorView) {
            $_SESSION[self::SESSION_VISITOR_VIEW] = true;
        } else {
            unset($_SESSION[self::SESSION_VISITOR_VIEW]);
        }
    }

    /**
     * Returns the access check. It respects the visitor view of managers.
     *
     * @return Access
     */
    protected function getAccess(): Access
    {
        return new Access(null, !$this->isVisitorView());
    }

    /**
     * Returns the programs a user takes part in, each with its progress.
     *
     * @param int $userId
     * @return array<int, array{enrollment: \Modules\Fitness\Models\Enrollment, program: \Modules\Fitness\Models\Program, progress: \Modules\Fitness\Models\Progress}>
     */
    protected function getTrainingsOfUser(int $userId): array
    {
        return (new Trainings())->getOfUser($userId);
    }

    /**
     * Collects the milestones of a user for the overview pages. Milestones the user has reached
     * without receiving them yet (for example because they were created later) are stored now.
     *
     * @param int $userId
     * @param array $trainings result of getTrainingsOfUser()
     * @return array{milestones: MilestoneModel[], achievements: array<int, string>, stats: array, totals: array{sessions: int, phases: int, programs: int}}
     */
    protected function getMilestoneOverview(int $userId, array $trainings): array
    {
        $stats = Milestones::getStatsOfTrainings($trainings);
        (new Milestones())->evaluate($userId, null, $stats);

        $milestoneMapper = new MilestoneMapper();

        return [
            'milestones' => Milestones::getRelevantMilestones($milestoneMapper->getActiveMilestones(), $stats),
            'achievements' => $milestoneMapper->getAchievementsOfUser($userId),
            'stats' => $stats,
            'totals' => Milestones::getTotals($stats),
        ];
    }

    /**
     * Tells the user about new milestones: as a notification of the website and on the next
     * page of the fitness area (see takeReachedMilestones()).
     *
     * @param int $userId
     * @param MilestoneModel[] $milestones
     */
    protected function announceMilestones(int $userId, array $milestones): void
    {
        if (!$milestones) {
            return;
        }

        $url = $this->getLayout()->getUrl(['module' => 'fitness', 'controller' => 'milestones', 'action' => 'index']);
        $notificationsMapper = new NotificationsMapper();

        foreach ($milestones as $milestone) {
            $_SESSION[self::SESSION_REACHED_MILESTONES][] = $milestone->getId();

            // The title goes in as argument, so a "%" in an own title can't break the text.
            $message = $this->getTranslator()->trans('milestoneNotification', $milestone->getDisplayTitle($this->getTranslator()));
            $notificationsMapper->addNotification((new NotificationModel())
                ->setUserId($userId)
                ->setModule('fitness')
                ->setMessage(mb_substr($message, 0, 255))
                ->setURL($url)
                ->setType('milestoneReached'));
        }
    }

    /**
     * Returns the milestones announced by announceMilestones() and forgets them, so they are
     * shown only once.
     *
     * @return MilestoneModel[]
     */
    protected function takeReachedMilestones(): array
    {
        $ids = array_map('intval', (array)($_SESSION[self::SESSION_REACHED_MILESTONES] ?? []));
        unset($_SESSION[self::SESSION_REACHED_MILESTONES]);

        if (!$ids) {
            return [];
        }

        return (new MilestoneMapper())->getEntriesBy(['m.id' => array_values(array_unique($ids))]);
    }

    /**
     * Sends guests to the login page.
     */
    protected function requireLogin(): void
    {
        if (!$this->getUser()) {
            $this->redirect()
                ->withMessage('loginRequired', 'info')
                ->to(['module' => 'user', 'controller' => 'login', 'action' => 'index']);
        }
    }
}
