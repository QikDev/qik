<?php 

namespace Qik\Core;

interface APIObject
{
	function GetModel() : array;
	function GetPublicModel() : array;
}