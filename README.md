-----------------------------Framework BT-----------------------------
To use this:
	1. put the following in the composer.json
		{
		    "require": {
		        "browntape_tech/bt_framework":"dev-archit"
		    },
		    "config": {
		        "vendor-dir": "vendor"
		    },
		    "repositories": [
		        {
		            "type": "vcs",
		            "url": "git@bitbucket.org:browntape_tech/bt_framework.git"
		        }
		    ]
		}

	2. Change the config.php and constants.php