The functionality should be reviewed and applied manually.

### The 'ypkc_activity_finder' module.
It provides customization related to OpenY's Activity Finder. On the YPKC we use solr as storage for AF related data. In this module, we override the backend and controller to implement custom logic (e.g. refactor the backend, improved caching, etc.)

### Patches.
We use 4.1.4 version of the ycloudyusa/yusaopeny_activity_finder module. Also, we apply such patches, to customize AF:
```
"ycloudyusa/yusaopeny_activity_finder": {
    "YP2-50: increase age filters maximum count": "patches/openy_activity_finder/yp2-50-increase-age-filters-count.patch",
    "Remove homebranch pre-filtering": "patches/openy_activity_finder/Remove_homebranch_pre-filtering.patch",
    "Fix styles for tablet screens": "patches/openy_activity_finder/fix_media_breakpoints_on_results_for_tablet.patch",
    "Display location link instead of title in AF4": "patches/openy_activity_finder/location_link_instead_title.patch",
    "YP2-48: update layout of hours block, update scrolling on step changing.": "patches/openy_activity_finder/yp2_48_style_improvements.patch",
    "GTM event tracking": "patches/openy_activity_finder/push_GTM_events.patch",
    "Enhancement for the fielte by Age: takes into account intermediate values": "patches/openy_activity_finder/age_filter_enhancement.patch",
    "Fix broken config import after upgrade(configs from update applied manually).": "patches/openy_activity_finder/fix-broken-config-import-on-deploy.patch",
    "3285387 - Compatibility with PHP 8.1": "https://patch-diff.githubusercontent.com/raw/YCloudYUSA/yusaopeny_activity_finder/pull/3.patch",
    "Show all activities on the activity step, If not limited by category.": "patches/openy_activity_finder/show_all_activities_on_activity_step.patch",
    "Ability to have pre-filtered results": "patches/openy_activity_finder/prefiltered_results.patch",
    "YP4-101: Activity finder filters changes.": "patches/openy_activity_finder/filters_changes.patch",
    "YP4-104: Disable/enable bookmarks functionality": "patches/openy_activity_finder/disable_enable_bookmarks_functionality.patch",
    "YPKCOY-430: Add programs": "patches/openy_activity_finder/add_programs_to_list_and_modal.patch"
},
```

### Solr config.
In 'config' folder is preset the configuration of the search api index that is used in AF backend. All fields that are present in this config, should be present in the appropriate entities.

