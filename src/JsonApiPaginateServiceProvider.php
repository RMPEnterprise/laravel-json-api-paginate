<?php

namespace Spatie\JsonApiPaginate;

use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Builder;

class JsonApiPaginateServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/json-api-paginate.php' => config_path('json-api-paginate.php'),
            ], 'config');
        }

        $this->registerMacro();
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/json-api-paginate.php', 'json-api-paginate');
    }

    protected function registerMacro()
    {
        Builder::macro(config('json-api-paginate.method_name'), function (int $maxResults = null, int $defaultSize = null) {
            $maxResults = $maxResults ?? config('json-api-paginate.max_results');
            $defaultSize = $defaultSize ?? config('json-api-paginate.default_size');
            $numberParameter = config('json-api-paginate.number_parameter');
            $sizeParameter = config('json-api-paginate.size_parameter');
            $paginationParameter = config('json-api-paginate.pagination_parameter');

            $size = (int) request()->input($paginationParameter.'.'.$sizeParameter, $defaultSize);

            if ($maxResults && $size > $maxResults) {
                $size = $maxResults;
            }

            if ($size === -1) {
                $size = $this->toBase()->getCountForPagination();
            }

            $paginator = $this
                ->paginate($size, ['*'], $paginationParameter.'.'.$numberParameter)
                ->setPageName($paginationParameter.'['.$numberParameter.']')
                ->appends(Arr::except(request()->input(), $paginationParameter.'.'.$numberParameter));

            if (!is_null(config('json-api-paginate.base_url'))) {
                $paginator->setPath(config('json-api-paginate.base_url'));
            }

            return $paginator;
        });
    }
}
