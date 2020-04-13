<?php
/**
 Copyright (C) 2018-2020 KANOUN Salim
 This program is free software; you can redistribute it and/or modify
 it under the terms of the Affero GNU General Public v.3 License as published by
 the Free Software Foundation;
 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 Affero GNU General Public Public for more details.
 You should have received a copy of the Affero GNU General Public Public along
 with this program; if not, write to the Free Software Foundation, Inc.,
 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA
 */

?><!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="utf-8" />

	<meta name="description" content="Open Health Imaging Foundation DICOM Viewer" />
	<meta name="viewport" content="width=device-width,initial-scale=1.0,minimum-scale=1,maximum-scale=1,user-scalable=no" />
	<meta name="theme-color" content="#000000" />
	<meta http-equiv="cleartype" content="on" />
	<meta name="MobileOptimized" content="320" />
	<meta name="HandheldFriendly" content="True" />
	<meta name="apple-mobile-web-app-capable" content="yes" />

	<!-- Latest compiled and minified CSS -->
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u"
	 crossorigin="anonymous" />

	<!-- WEB FONTS -->
	<link href="https://fonts.googleapis.com/css?family=Sanchez" rel="stylesheet" />
	<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.2/css/all.css" integrity="sha384-fnmOCqbTlWIlj8LyTjo7mOUStjsKC4pOpQbqyi7RrhN7udi9RwhKkMHpvLbHG9Sr"
	 crossorigin="anonymous" />

	<title>OHIF Standalone Viewer</title>
</head>

  <body>
    <noscript> You need to enable JavaScript to run this app. </noscript>

    <div id="root"></div>
    <script crossorigin src="https://unpkg.com/@ohif/viewer/dist/index.umd.js"></script>
    <script crossorigin src="https://unpkg.com/@ohif/extension-vtk/dist/index.umd.js"></script>
    <script crossorigin src="https://unpkg.com/@ohif/extension-cornerstone/dist/index.umd.js"></script>
    <script crossorigin src="https://unpkg.com/@ohif/extension-dicom-html/dist/index.umd.js"></script>
    <script
    src="https://polyfill.io/v3/polyfill.min.js?flags=gated&features=default%2CObject.values%2CArray.prototype.flat%2CObject.entries%2CSymbol%2CArray.prototype.includes%2CString.prototype.repeat%2CArray.prototype.find"></script>
    <script>
      var containerId = "root";
      var componentRenderedOrUpdatedCallback = function() {
          console.log("OHIF Viewer rendered/updated");
        };
      window.OHIFViewer.installViewer(
        {
          routerBasename: '/ohif',
          whiteLabelling: {},
          cornerstoneExtensionConfig: {},
          maxConcurrentMetadataRequests: 1,
          extensions: [OHIFExtCornerstone, OHIFExtDicomHtml],
          showStudyList: false,
          filterQueryParam: false,
          servers: {
            dicomWeb: [
              {
                name: "Orthanc",
                wadoUriRoot:
                  "/orthanc/wado",
                qidoRoot:
                  "/orthanc/dicom-web",
                wadoRoot:
                  "/orthanc/dicom-web",
                qidoSupportsIncludeField: true,
                imageRendering: "wadors",
                thumbnailRendering: "wadors",
                enableStudyLazyLoad: true
              }
            ]
          }
        },
        containerId,
        componentRenderedOrUpdatedCallback
      );

    </script>

  </body>

</html>
