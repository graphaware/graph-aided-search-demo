# GraphAware GraphAidedSearch Demo

Docker Compose setup for testing `Graph-Aided Search` combining Elasticsearch and Neo4j.

The following will be run and configured automatically :

* Elasticsearch 2.2.2 listening on port 9200 and the `Graph-Aided Search` plugin installed
* Neo4j 2.3.3 listening on port 7474
* GraphAware [neo4j-framework](https://github.com/graphaware/neo4j-framework) and GraphAware [neo4j-to-elasticsearch](https://github.com/graphaware/neo4j-to-elasticsearch) plugin installed and configured
* CSV sources for running the examples installed in the `data/import` directory of Neo4j and the server is
configured to allow loading external files from this path
* A `_query` directory providing json files of all the queries from the demo

### Setup

```bash
# Create a network bridge
docker network create --driver bridge gas

# Export the network name "gas" as environment variable
# bash
export NETWORK=gas
#fish-shell
set -x NETWORK "gas"

# Run the stack with Docker Compose
docker-compose up

# wait neo4j and elasticsearch are initialized
Starting neo4j
Starting elastic_container
Attaching to neo4j, elastic_container
neo4j             | Starting Neo4j Server console-mode...
elastic_container | [2016-04-08 00:52:47,608][INFO ][node                     ] [James "Jimmy" Marks] version[2.2.2], pid[1], build[fcc01dd/2016-03-29T08:49:35Z]
elastic_container | [2016-04-08 00:52:47,631][INFO ][node                     ] [James "Jimmy" Marks] initializing ...
neo4j             | 2016-04-08 00:52:49.566+0000 INFO  Initiating metrics..
elastic_container | [2016-04-08 00:52:50,388][INFO ][plugins                  ] [James "Jimmy" Marks] modules [lang-expression, lang-groovy], plugins [graph-aided-search], sites []
elastic_container | [2016-04-08 00:52:50,422][INFO ][env                      ] [James "Jimmy" Marks] using [1] data paths, mounts [[/usr/share/elasticsearch/data (/dev/sda1)]], net usable_space [182.6gb], net total_space [195.8gb], spins? [possibly], types [ext4]
elastic_container | [2016-04-08 00:52:50,427][INFO ][env                      ] [James "Jimmy" Marks] heap size [1015.6mb], compressed ordinary object pointers [true]
elastic_container | [2016-04-08 00:52:57,040][INFO ][node                     ] [James "Jimmy" Marks] initialized
elastic_container | [2016-04-08 00:52:57,044][INFO ][node                     ] [James "Jimmy" Marks] starting ...
elastic_container | [2016-04-08 00:52:57,479][INFO ][transport                ] [James "Jimmy" Marks] publish_address {172.18.0.3:9300}, bound_addresses {[::]:9300}
elastic_container | [2016-04-08 00:52:57,513][INFO ][discovery                ] [James "Jimmy" Marks] docker/MandNLv-RHekkkaZajymKQ
neo4j             | 2016-04-08 00:52:58.383+0000 INFO  Successfully started database
neo4j             | 2016-04-08 00:52:58.420+0000 INFO  Starting HTTP on port 7474 (1 threads available)
neo4j             | 2016-04-08 00:52:58.723+0000 INFO  Enabling HTTPS on port 7473
neo4j             | 2016-04-08 00:52:58.728+0000 INFO  Mounted REST API at: /db/manage
neo4j             | 2016-04-08 00:52:58.742+0000 INFO  Mounted unmanaged extension [com.graphaware.server] at [/graphaware]
neo4j             | 2016-04-08 00:52:58.852+0000 INFO  Mounting static content at /webadmin
neo4j             | 2016-04-08 00:52:58.937+0000 INFO  Mounting static content at /browser
elastic_container | [2016-04-08 00:53:00,619][INFO ][cluster.service          ] [James "Jimmy" Marks] new_master {James "Jimmy" Marks}{MandNLv-RHekkkaZajymKQ}{172.18.0.3}{172.18.0.3:9300}, reason: zen-disco-join(elected_as_master, [0] joins received)
elastic_container | [2016-04-08 00:53:00,690][INFO ][http                     ] [James "Jimmy" Marks] publish_address {172.18.0.3:9200}, bound_addresses {[::]:9200}
elastic_container | [2016-04-08 00:53:00,699][INFO ][node                     ] [James "Jimmy" Marks] started
elastic_container | [2016-04-08 00:53:00,770][INFO ][gateway                  ] [James "Jimmy" Marks] recovered [0] indices into cluster_state
neo4j             | 2016-04-08 00:53:04.976+0000 INFO  Remote interface ready and available at http://0.0.0.0:7474/
```

Neo4j will be available on your docker host ip (generally `localhost` on Linux and `192.168.99.100` on OS X) on port 7474 and Elasticsearch on port 9200.

#### Configure the plugin settings

```bash
curl -XPUT http://192.168.99.100:9200/neo4j-index/_settings?index.gas.neo4j.hostname=http://localhost:7474&index.gas.enable=true
```

```bash
ikwattro@graphaware ~> curl -XPUT "http://192.168.99.100:9200/neo4j-index/_settings?index.gas.neo4j.hostname=http://localhost:7474&index.gas.enable=true"
{"acknowledged":true}~                                                                                                                                                                                 ikwattro@graphaware ~>
```

#### Add the schema constraints in Neo4j

Open up the browser and run these 2 cypher statements :

```bash
CREATE CONSTRAINT ON (n:Movie) ASSERT n.objectId IS UNIQUE;

CREATE CONSTRAINT ON (n:User) ASSERT n.objectId IS UNIQUE;
```

Create the Users from the CSV dataset :

```bash
USING PERIODIC COMMIT 500
LOAD CSV FROM "file:///u.user" AS line FIELDTERMINATOR '|'
CREATE (:User {objectId: toInt(line[0]), age: toInt(line[1]), gender: line[2], occupation: line[3]});
```

Create the Movies :

```bash
USING PERIODIC COMMIT 500
LOAD CSV FROM "file:///u.item" AS line FIELDTERMINATOR '|'
CREATE (:Movie {objectId: toInt(line[0]), title: line[1], date: line[2], imdblink: line[4]});
```

Create the Likes relationships with the ratings as relationship properties :

```bash
USING PERIODIC COMMIT 500
LOAD CSV FROM "file:///u.data" AS line FIELDTERMINATOR '\t'
MATCH (u:User {objectId: toInt(line[1])})
MATCH (p:Movie {objectId: toInt(line[0])})
CREATE UNIQUE (u)-[:LIKES {rate: ROUND(toFloat(line[2])), timestamp: line[3]}]->(p);
```

#### Query 1 : Simple search for movies with `love` in the title

```bash
GET /neo4j-index/_search?pretty HTTP/1.1
Content-Length: 108
Accept-Encoding: gzip, deflate
Host: 192.168.99.100:9200
Accept: application/json
User-Agent: HTTPie/0.9.2
Connection: keep-alive
Content-Type: application/json

{
  "size": 3,
  "query" : {
      "bool": {
        "should": [{"match": {"title": "love"}}]
      }
  }
}


HTTP/1.1 200 OK
Content-Type: application/json; charset=UTF-8
Content-Length: 1189

{
  "took" : 6,
  "timed_out" : false,
  "_shards" : {
    "total" : 5,
    "successful" : 5,
    "failed" : 0
  },
  "hits" : {
    "total" : 29,
    "max_score" : 2.9627202,
    "hits" : [ {
      "_index" : "neo4j-index",
      "_type" : "Movie",
      "_id" : "1297",
      "_score" : 2.9627202,
      "_source" : {
        "date" : "01-Jan-1994",
        "imdblink" : "http://us.imdb.com/M/title-exact?Love%20Affair%20(1994)",
        "title" : "Love Affair (1994)",
        "objectId" : "1297"
      }
    }, {
      "_index" : "neo4j-index",
      "_type" : "Movie",
      "_id" : "1446",
      "_score" : 2.919132,
      "_source" : {
        "date" : "01-Jan-1995",
        "imdblink" : "http://us.imdb.com/M/title-exact?Bye%20Bye,%20Love%20(1995)",
        "title" : "Bye Bye, Love (1995)",
        "objectId" : "1446"
      }
    }, {
      "_index" : "neo4j-index",
      "_type" : "Movie",
      "_id" : "535",
      "_score" : 2.6663055,
      "_source" : {
        "date" : "23-May-1997",
        "imdblink" : "http://us.imdb.com/M/title-exact?Addicted%20to%20Love%20%281997%29",
        "title" : "Addicted to Love (1997)",
        "objectId" : "535"
      }
    } ]
  }
}
```

#### Query2 : Filter out negatively rated movies from the ES results by using a Cypher filter :

```bash
GET /neo4j-index/_search?pretty HTTP/1.1
Content-Length: 351
Accept-Encoding: gzip, deflate
Host: 192.168.99.100:9200
Accept: application/json
User-Agent: HTTPie/0.9.2
Connection: keep-alive
Content-Type: application/json

{
  "size": 3,
  "query" : {
      "bool": {
        "should": [{"match": {"title": "love"}}]
      }
  },
  "gas-filter" :{
      "name": "SearchResultCypherFilter",
      "query": "MATCH (n:User)-[r:LIKES]->(m) WITH m, avg(r.rate) as avg_rate where avg_rate < 3 RETURN m.objectId as id",
      "exclude": true,
      "keyProperty": "objectId"
  }
}


HTTP/1.1 200 OK
Content-Type: application/json; charset=UTF-8
Content-Length: 1189

{
  "took" : 4,
  "timed_out" : false,
  "_shards" : {
    "total" : 5,
    "successful" : 5,
    "failed" : 0
  },
  "hits" : {
    "total" : 29,
    "max_score" : 2.9627202,
    "hits" : [ {
      "_index" : "neo4j-index",
      "_type" : "Movie",
      "_id" : "1297",
      "_score" : 2.9627202,
      "_source" : {
        "date" : "01-Jan-1994",
        "imdblink" : "http://us.imdb.com/M/title-exact?Love%20Affair%20(1994)",
        "title" : "Love Affair (1994)",
        "objectId" : "1297"
      }
    }, {
      "_index" : "neo4j-index",
      "_type" : "Movie",
      "_id" : "1446",
      "_score" : 2.919132,
      "_source" : {
        "date" : "01-Jan-1995",
        "imdblink" : "http://us.imdb.com/M/title-exact?Bye%20Bye,%20Love%20(1995)",
        "title" : "Bye Bye, Love (1995)",
        "objectId" : "1446"
      }
    }, {
      "_index" : "neo4j-index",
      "_type" : "Movie",
      "_id" : "535",
      "_score" : 2.6663055,
      "_source" : {
        "date" : "23-May-1997",
        "imdblink" : "http://us.imdb.com/M/title-exact?Addicted%20to%20Love%20%281997%29",
        "title" : "Addicted to Love (1997)",
        "objectId" : "535"
      }
    } ]
  }
}

```

#### Query 3 : Exclude movies already rated by the User

We randomly choose the User with id `12`, this logic should be computed at the application level

```bash
GET /neo4j-index/_search?pretty HTTP/1.1
Content-Length: 330
Accept-Encoding: gzip, deflate
Host: 192.168.99.100:9200
Accept: application/json
User-Agent: HTTPie/0.9.2
Connection: keep-alive
Content-Type: application/json

{
  "size": 3,
   "query" : {
       "bool": {
         "should": [{"match": {"title": "love"}}]
       }
   },
   "gas-filter" :{
        "name": "SearchResultCypherFilter",
        "query": "MATCH (n:User {objectId: 12})-[r:LIKES]->(m) RETURN m.objectId as id",
        "exclude": true,
        "keyProperty": "objectId"
   }
}


HTTP/1.1 200 OK
Content-Type: application/json; charset=UTF-8
Content-Length: 1189

{
  "took" : 9,
  "timed_out" : false,
  "_shards" : {
    "total" : 5,
    "successful" : 5,
    "failed" : 0
  },
  "hits" : {
    "total" : 29,
    "max_score" : 2.9627202,
    "hits" : [ {
      "_index" : "neo4j-index",
      "_type" : "Movie",
      "_id" : "1297",
      "_score" : 2.9627202,
      "_source" : {
        "date" : "01-Jan-1994",
        "imdblink" : "http://us.imdb.com/M/title-exact?Love%20Affair%20(1994)",
        "title" : "Love Affair (1994)",
        "objectId" : "1297"
      }
    }, {
      "_index" : "neo4j-index",
      "_type" : "Movie",
      "_id" : "1446",
      "_score" : 2.919132,
      "_source" : {
        "date" : "01-Jan-1995",
        "imdblink" : "http://us.imdb.com/M/title-exact?Bye%20Bye,%20Love%20(1995)",
        "title" : "Bye Bye, Love (1995)",
        "objectId" : "1446"
      }
    }, {
      "_index" : "neo4j-index",
      "_type" : "Movie",
      "_id" : "535",
      "_score" : 2.6663055,
      "_source" : {
        "date" : "23-May-1997",
        "imdblink" : "http://us.imdb.com/M/title-exact?Addicted%20to%20Love%20%281997%29",
        "title" : "Addicted to Love (1997)",
        "objectId" : "535"
      }
    } ]
  }
}
```
