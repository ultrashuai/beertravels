"# beertravels" 

The Beer Travels application utilizes six components to automatically load and display photos and metadata in a chronological order.
1. Flickr API: Photos are obtained from a user stream based on a particular "tag". Tags other than that particular tag may be used to automatically populate other metadata. Your Flickr user ID is required for this.
2. Yelp API: The business name in the description of the photo pulled from Flickr is used in a Yelp API query. The address, phone number and geographic location information are stored remotely to prevent future unnecessary calls to the Yelp API.
3. PHP: Calls to the Flickr and Yelp APIs, in order to pull and populate new information, are handled server-side in PHP. Additionally PHP is used to interact with MySQL.
4. MySQL: A MySQL database stores the pictures' metadata.
5. Javascript: Javascript is used to build the Google Maps map object.
6. Google Maps API: Points are populated into a Google Maps map with info windows displaying the metadata of the photo.

Requirements:
1. Flickr API Key
2. Flickr account with user ID
3. Yelp API Key
4. Google Maps API Key
5. PHP
6. MySQL

Usage:
1. Add a photo to your Flickr stream, using the tag you have entered into the code to identify photos to be used. Enter the business name in the description field.
2. Browse to the homepage of the site. Your new photo will be automatically populated into the database, with location information.
3. You may use the administrator interface to enter additional information about the location.
