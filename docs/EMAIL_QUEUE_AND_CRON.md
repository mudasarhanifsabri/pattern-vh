# Email Queue and Recurring Jobs

This app uses Laravel's database queue:

```env
QUEUE_CONNECTION=database
```

That means emails and queued notifications are saved into the `jobs` table first. They will not send unless a queue worker is running.

## Required Always-On Processes

Run these two processes in production:

```bat
start-queue-worker.bat
start-scheduler.bat
```

The queue worker sends queued emails and notifications.

The scheduler checks recurring jobs, such as booking reminders and invoice reminders.

If either process is stopped:

- Welcome emails and notifications stay pending in the queue.
- Recurring booking and invoice reminders do not run.
- Restarting the queue later will send pending queued jobs.

After every deployment, run:

```bash
php artisan optimize:clear
php artisan queue:restart
```

## cPanel or Linux Cron Setup

Add this cron entry to run Laravel's scheduler every minute:

```cron
* * * * * cd /home/USER/PATH_TO_PROJECT && php artisan schedule:run >> /dev/null 2>&1
```

Also keep the queue worker running continuously. On a VPS, use Supervisor or systemd:

```bash
php artisan queue:work database --queue=default --tries=3 --timeout=120 --sleep=3
```

On shared hosting without Supervisor, create a cron job every minute:

```cron
* * * * * cd /home/USER/PATH_TO_PROJECT && php artisan queue:work database --queue=default --tries=3 --timeout=120 --sleep=3 --stop-when-empty >> /dev/null 2>&1
```

## Windows Server or Local Machine

Use Windows Task Scheduler:

1. Create one task for `start-queue-worker.bat`.
2. Create one task for `start-scheduler.bat`.
3. Set both tasks to run at startup.
4. Enable "Restart every 1 minute" if the task fails.

## Useful Commands

Retry failed jobs:

```bash
php artisan queue:retry all
```

List scheduled jobs:

```bash
php artisan schedule:list
```

Restart queue workers after deployment:

```bash
php artisan queue:restart
```
