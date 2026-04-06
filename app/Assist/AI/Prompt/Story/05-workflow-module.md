```php
$workflow = Workflow::make()

    /*
     |--------------------------------------------------------------------------
     | Nodes
     |--------------------------------------------------------------------------
     */

    ->node('fetch_news', NewsNode::class, [
        'cache' => ['ttl' => 300],
        'retry' => 3,
        'timeout' => 2000,
        'on_error' => 'fallback_news',
        'tags' => ['io', 'news']
    ])

    ->node('fetch_prices', PriceNode::class, [
        'retry' => 2,
        'timeout' => 1000,
        'tags' => ['io', 'market']
    ])

    ->node('fallback_news', FallbackNode::class)

    ->node('analyze', LLMNode::class, [
        'wait_for' => 'all', // all | any
        'prompt' => 'Analyze geopolitical risk based on given inputs',
        'tools' => ['summarize_news', 'detect_anomaly'],
        'model' => 'gpt-4.1',
        'temperature' => 0.2,
        'output_schema' => [
            'risk_score' => 'float',
            'summary' => 'string'
        ]
    ])

    ->node('store', DbNode::class, [
        'retry' => 1
    ])

    ->node('present', PresentNode::class)

    ->node('alert', AlertNode::class)

    ->node('end', EndNode::class)

    /*
     |--------------------------------------------------------------------------
     | Connections (Data Mapping + Flow)
     |--------------------------------------------------------------------------
     */

    ->connect('fetch_news', 'analyze', [
        'map' => [
            'response' => 'news_data'
        ]
    ])

    ->connect('fetch_prices', 'analyze', [
        'map' => [
            'response' => 'market_data'
        ]
    ])

    ->connect('analyze', 'store', [
        'map' => [
            'risk_score' => 'risk_score',
            'summary' => 'analysis'
        ]
    ])

    ->connect('analyze', 'present')

    /*
     |--------------------------------------------------------------------------
     | Conditional Branching
     |--------------------------------------------------------------------------
     */

    ->branch('analyze', function ($ctx) {
        return $ctx->get('risk_score') > 0.7;
    }, 'alert', 'end')

    /*
     |--------------------------------------------------------------------------
     | Parallel Groups
     |--------------------------------------------------------------------------
     */

    ->parallel(['fetch_news', 'fetch_prices'])

    /*
     |--------------------------------------------------------------------------
     | Global Hooks (Observability)
     |--------------------------------------------------------------------------
     */

    ->onNodeStart(function ($nodeId, $ctx) {
        logger()->info("Node started: {$nodeId}");
    })

    ->onNodeEnd(function ($nodeId, $ctx, $duration) {
        logger()->info("Node finished: {$nodeId} in {$duration}ms");
    })

    ->onError(function ($nodeId, $e, $ctx) {
        logger()->error("Error in {$nodeId}: " . $e->getMessage());
    })

    /*
     |--------------------------------------------------------------------------
     | Validation
     |--------------------------------------------------------------------------
     */

    ->validate();

$context = Context::make([
    'input' => [
        'region' => 'middle_east'
    ]
]);

$engine = new Engine($workflow, $context, [
    'mode' => 'async', // sync | async
    'max_concurrency' => 10
]);

$result = $engine->run();


$ctx->get('fetch_news.response');
$ctx->get('analyze.risk_score');

$ctx->set('meta.execution_id', uuid());

```
