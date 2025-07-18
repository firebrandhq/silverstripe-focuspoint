<?php

use SilverStripe\Assets\Image;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Dev\BuildTask;
use SilverStripe\Dev\Debug;
use SilverStripe\ORM\ValidationException;
use SilverStripe\Versioned\Versioned;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Command\Command;

class HydrateFocusPointTask extends BuildTask
{
    protected static string $commandName = 'HydrateFocusPointTask';

    protected string $title = 'Hydrate the focuspoint extension image size cache';

    protected static string $description = 'Run this task to cache all image sizes, and speed up image generation';

    /**
     * @param HTTPRequest $request
     * @throws ValidationException
     */
    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        // Get all images missing a width / height
        $images = Versioned::get_by_stage(Image::class, Versioned::DRAFT)->filterAny([
            'FocusPointWidth'  => 0,
            'FocusPointHeight' => 0,
        ]);
        Debug::message('Found ' . $images->count() . ' images to hydrate');

        /** @var Image $image */
        foreach ($images as $image) {
            // Skip images that aren't on the filesystem
            if (!$image->exists()) {
                continue;
            }

            // Save, and maybe publish
            $image->write();

            if ($image->isPublished()) {
                $image->publishSingle();
            }
        }

        return Command::SUCCESS;
    }
}
