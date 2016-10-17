<?php
/** 
 * @author Ruslan Gumennyi <ruslan.gumennyi@gmail.com>
 * @package RG_ImportSubscribers
 * 
 * This simple extension allows you to import your subscriber list 
 * from previously exported file as well as from scratch. 
 * Currently supported file formats: 
 * 		txt (only list of emails, one per line),
 * 		csv ('Email' is required field, other can be omitted) 
 * 		Excel XML (same as csv)  
 * 
 * Order of the columns can be anything, but it's important to pass correct headings, 
 * for example: 'Email' (not 'email'), 'Store View', 'Status', etc. 
 * 
 * Valid column names (any other will be ignored):
 * 		Email - email addresses
 * 		
 * 		Type  - can be Customer or Guest 
 * 			    IMPORTANT: If the Type specified as Customer, but the client 
 * 				with such e-mail doesn't exist, it will be saved as Guest
 * 		
 * 		Status - One of Subscribed, Unsubscribed, Not Activated, Unconfirmed  
 * 		
 * 		Store View - name of your site's store view in which you want 
 * 					 insert subscribers list (must be created prior to proccess,
 * 					 otherwise default will be used ) 
 * 
 * If count of rows in your file more than 50-60 thousands, 
 * you'd better break it for several smaller parts.
 * On my testing environment it took me 94 sec (1 min 34 sec) 
 * for importing 50000 rows with registered customers.
 * 
 * IMPORTANT: It doesn't care of duplicates, so you should remove them manualy 
 * prior to import       
 * 
 */
class RG_ImportSubscribers_Exception extends Zend_Exception
{
}
