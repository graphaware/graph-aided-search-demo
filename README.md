# GraphAware GraphAidedSearch Demo

### Setup

```bash
docker network create --driver bridge gas

# bash
export NETWORK=gas

#fish-shell
set -x NETWORK "gas"

docker-compose up
```

Configure the plugin settings :

```bash
curl -XPUT http://192.168.99.100:9200/neo4j-index/_settings?index.gas.neo4j.hostname=http://localhost:7474&index.gas.enable=true
```

```bash
ikwattro@graphaware ~> curl -XPUT "http://192.168.99.100:9200/neo4j-index/_settings?index.gas.neo4j.hostname=http://localhost:7474&index.gas.enable=true"
{"acknowledged":true}~                                                                                                                                                                                 ikwattro@graphaware ~>
```

#### Query 1 : Simple search for movies with `love` in the title

```bash
ikwattro@graphaware ~/d/p/tx-dashboard> http GET "http://192.168.99.100:9200/neo4j-index/_search?pretty" < _query/01_simple_search.json
HTTP/1.1 200 OK
Content-Length: 1190
Content-Type: application/json; charset=UTF-8

{
    "_shards": {
        "failed": 0,
        "successful": 5,
        "total": 5
    },
    "hits": {
        "hits": [
            {
                "_id": "1297",
                "_index": "neo4j-index",
                "_score": 2.9627202,
                "_source": {
                    "date": "01-Jan-1994",
                    "imdblink": "http://us.imdb.com/M/title-exact?Love%20Affair%20(1994)",
                    "objectId": "1297",
                    "title": "Love Affair (1994)"
                },
                "_type": "Movie"
            },
            {
                "_id": "1446",
                "_index": "neo4j-index",
                "_score": 2.919132,
                "_source": {
                    "date": "01-Jan-1995",
                    "imdblink": "http://us.imdb.com/M/title-exact?Bye%20Bye,%20Love%20(1995)",
                    "objectId": "1446",
                    "title": "Bye Bye, Love (1995)"
                },
                "_type": "Movie"
            },
            {
                "_id": "535",
                "_index": "neo4j-index",
                "_score": 2.6663055,
                "_source": {
                    "date": "23-May-1997",
                    "imdblink": "http://us.imdb.com/M/title-exact?Addicted%20to%20Love%20%281997%29",
                    "objectId": "535",
                    "title": "Addicted to Love (1997)"
                },
                "_type": "Movie"
            }
        ],
        "max_score": 2.9627202,
        "total": 29
    },
    "timed_out": false,
    "took": 46
}
```

#### Query2 : Filter out negatively rated movies from the ES results by using a Cypher filter :
