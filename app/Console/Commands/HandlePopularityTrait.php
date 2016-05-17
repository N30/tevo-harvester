<?php

namespace TevoHarvester\Console\Commands;

use TevoHarvester\Jobs\UpdatePerformerPopularityJob;
use TevoHarvester\Tevo\Category;
use TevoHarvester\Tevo\Harvest;


trait HandlePopularityTrait
{
    /**
     * Execute the console command.
     *
     * @return mixed
     */
    protected function handlePopularity()
    {
        $resource = 'performers';
        $action = 'popularity';

        try {
            $harvest = Harvest::where('resource', $resource)->where('action', $action)->firstOrFail();
        } catch (\Exception $e) {
            $this->info('There is no existing action for updating ' . ucwords($action) . ' ' . ucwords($resource) . '.');
            exit('Nothing was updated.');
        }

        /**
         * Get all the categories and then loop through them creating an
         * UpdatePerformerPopularityJob for each one.
         */
        try {
            $categories = Category::all(['id']);
        } catch (\Exception $e) {
            abort(404, 'There are no categories yet. Please ensure you have run the Active Categories job.');
        }

        // Get the last category->id so we can later detect that the Job for that
        // category->id has completed in order to know to fire the ResourceUpdateWasCompleted Event
        $last_category_id = $categories->last()->id;

        $message = 'Updating the popularity_score for the 100 most popular Performers in each Category.';
        $this->info($message);

        foreach ($categories->toArray() as $category) {
            $job = new UpdatePerformerPopularityJob($harvest, $category['id'], $last_category_id);

            $this->dispatch($job);
        }

    }
}
