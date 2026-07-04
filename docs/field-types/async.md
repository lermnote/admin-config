---
title: Async
parent: Field Types
nav_order: 6
---

# Async Field Types

## ajax_select

Search-as-you-type select backed by a registered data source. Requires a
`source` key matching a data source registered via
`$runtime->register_data_source()` — see
[Extension Recipes](/extension-recipes) for how to register one. Add
`'multiple' => true` for a multi-select variant.

```php
// Single select
array(
    'id'                => 'featured_campaign',
    'type'              => 'ajax_select',
    'source'            => 'campaign_library',
    'label'             => __( 'Featured Campaign', 'my-plugin' ),
    'placeholder'       => __( 'Search campaigns...', 'my-plugin' ),
    'min_search_length' => 1,
    'per_page'          => 4,
    'default'           => 'spring-launch',
),

// Multi-select
array(
    'id'                => 'supporting_campaigns',
    'type'              => 'ajax_select',
    'source'            => 'campaign_library',
    'multiple'          => true,
    'label'             => __( 'Supporting Campaigns', 'my-plugin' ),
    'placeholder'       => __( 'Search campaigns...', 'my-plugin' ),
    'min_search_length' => 1,
    'per_page'          => 4,
    'default'           => array( 'creator-series', 'audio-week' ),
),
```

`min_search_length` sets how many characters trigger a search request;
`per_page` caps results per page for the paged dropdown. Both requests and
hydration of the stored value go through the REST transport documented in
[REST API](/rest-api).
