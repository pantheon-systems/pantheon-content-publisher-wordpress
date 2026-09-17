# Core | PCC PHP SDK

Contains core functionalities.

## Entity

Contains Entity (Article, ArticlesList) for internal interactions. 

### Entity Loaders

Contains Loaders to fetch entities from actual storage and load as entities.

## Query

Contains Query Objects allowing interactions via PccClient.

### Query Builder

Contains Builders that helps construct the query.

We are using `cfpinto/graphql` external library for Articles Query Builder. That allows us to build GraphQL queries in a
readable format. While empowering us to create and modify them as needed, achieving the required flexibility. 

## Services

Contains internal services that builds up the API. Here we have `ArticlesManager` responsible for fetching articles
for the API via loaders.
