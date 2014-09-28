<?php

namespace duncan3dc\Twitter;

use duncan3dc\Helpers\Dict;
use duncan3dc\Helpers\Helper;
use duncan3dc\Helpers\Json;
use duncan3dc\Laravel\Blade;

require(__DIR__ . "/../vendor/autoload.php");

switch ($_SERVER["REQUEST_URI"]) {


    case "/":
        echo Blade::make("index");
        break;


    case "/get-user-data":
        $data = [];

        $result = Sql::selectAll("status", [
            "type"      =>  "twitter",
        ]);
        foreach ($result as $row) {
            $data[$row["key"]] = $row["value"];
        }

        echo Json::encode([
            "status"    =>  1,
            "userdata"  =>  $data,
        ]);
        break;


    case "/get-unread-count":
        echo Json::encode([
            "status"    =>  1,
            "unread"    =>  App::getUnreadCount(),
        ]);
        break;


    case "/get-posts":
        $exclude = Dict::post("posts", []);
        $status = round(Dict::post("status", 0));
        $delay = round(Dict::post("delay", 0));

        $query = "SELECT * FROM posts
                WHERE status=?
                    AND date<?
                ORDER BY date, id
                LIMIT 20";
        $params = [
            $status,
            time() - ($delay * 60),
        ];
        $result = Sql::query($query, $params);

        $posts = [];
        foreach ($result as $row) {
            if (in_array($row["id"], $exclude)) {
                continue;
            }
            $post = new Post($row);
            $posts[] = [
                "id"    =>  $row["id"],
                "html"  =>  $post->make(),
            ];
        }

        echo Json::encode([
            "status"    =>  1,
            "posts"     =>  $posts,
            "unread"    =>  App::getUnreadCount(),
        ]);
        break;


    case "/update-post":
        Sql::update("posts", [
            "status"    =>  Dict::post("status"),
        ], [
            "id"        =>  Dict::post("post"),
        ]);

        echo Json::encode([
            "status"    =>  1,
            "unread"    =>  App::getUnreadCount(),
        ]);
        break;


    case "/get-hashtags":
        $hashtags = [];

        $query = "SELECT hashtag FROM hashtags
                GROUP BY hashtag
                ORDER BY COUNT(*) DESC, MAX(date) DESC
                LIMIT 10";
        $result = Sql::query($query);
        foreach ($result as $row) {
            $hashtags[] = $row["hashtag"];
        }

        echo Json::encode([
            "status"    =>  1,
            "hashtags"  =>  $hashtags,
        ]);
        break;


    default:
        throw new \Exception("Unknown request");
}