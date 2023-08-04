# YPKC Activity finder

Provide customization related to Activity finder.

## KEEP IN MIND
### OpenY Activity Finder upgrade
When we upgrade contrib Activity Finder, we should take care about config update of the Solr index carefully, as we added new fields to Solr, and operate with it, instead of loading original entities on AF request.

### Additional index.
We use additional index `Activity finder helper` to store additional data, to don't make a mess in the main index.
