# Slaho
Slaho (short for **SLA**~~ck~~ ~~Web~~**HO**~~ok~~), is a small PHP5 library for posting messages to Slack, using their webhooks.

It is meant to be very easy to use. I've had experience with several other Slack implementations and they were quite clumsy and overdressed for my tast. This is meant to be used only for posting messages using Slack webhooks. 

## Basic use
You create a new object by providing the Slack webhook to use. 

	$s = new slaho('https://hooks.slack.com/services/T2Y...lEavL9k');

Additional argument to the constructor is an array with formatting details that can be re-used as defaults for all messages being posted with this Slaho object. Those are the "username", "icon_url" anf "icon_emoji" settings:

	$s = new slaho('https://hooks.slack.com/servi...vR8z', [
		"username" => "Slaho",
		"icon_emoji" => ":cat:"
	]);

Once you have an object created, you can start posting messages with it like this:

	$s->message('Hello World');

These messages can have additional formatting provided by the second argument of `slaho::message()` method:

	$s->message('Hello World!', ['attachments' => [
		['text' => 'Attachment message']
	]]);

You can read more about Slack message and their formatting, links, attachments, etc in their docs.

If you have the whole JSON data that you need to post to Slack, you can do it by calling the `slack::post()` method directly like this:

	$s->post(json_encode(['username' => 'Me!', 'text' => 'G\'day, Boy-o!']));

## Callbacks for posting
The messages are send with HTTP POST requests towards the Slack webhooks. This is a very basic and common thing nowadays, but still it might offer some challenges denepding on the environment in whcih it is deployed.

In Slaho there are two built-in methods that are used to send the POST requests: one used the `curl` PHP extension (if it is installed), and the other used the `curl` binary (which it attempts to find on your system). This is all done under the hood and you do not have to do anything. 

However, you might want to  use a different mechanism to do the POST request. In Slaho there is a way to set your own callback to do that. Here's an example where I put a new callback which I am going to use for debugging - instead of posting the message, it will print it out:

	slaho::callback(function($json, $webhook) {
		var_dump([$json, $webhook]);
	});

The callbacks must take two arguments: first is the JSON encoded message that must be posted, and the second is the webhook URL to post to.

## Message formatting
Second argument of `slaho::message()` is for the extra formatting you can apply to the message. You can read more about this in Slack documentation. To make it easier to create and work with that formatting, there's a static array with two elements which are provided as examples: 

 * first one is of a basic message formatting (just with "username" and "icon_url"/"icon_emoji"), 
 * and the second one is with "attachments"

You can read them and use to create some messages with rich formatting:

	$s->message('Hello World!', slaho::$example[1]);
  
