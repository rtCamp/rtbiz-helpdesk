# Web Based Ticket UI

Below is the screenshot of how a ticket looks on the front end.

![Ticket UI](https://cloud.githubusercontent.com/assets/9676513/6439931/1e39f724-c0fe-11e4-8279-52f04a7ac460.jpg)


 ####  A. Ticket Content Area

'*' indicates the feature is visible only the staff and not the customer.


1. Ticket Title - A combination of the unique ID genearted for each ticket and the request subject set by the ticket author.

2. Ticket Author -  The contact name, which can be a email address if profile does not have a name associated.

3. Gravatar - Gravatar or default image as associated with ticket author's email.

4. Ticket Content - The data/text sent by the ticket author while sending/submitting ticket creation mail/form. Images can be attached with this content.

5. Collapsed follow-ups - The UI automatcially collapses ticket follow-ups if they are more than four. Click this button to expand and see all the follow-ups.

6. Time-stamp & reply permalink - The time at which a particular reply is added. It is linked to its permalink. Use this link to share a particular reply.

7. Edit reply - To be used when ticket content or a reply content needs to be updated.
#### B. Ticket Meta

8. Edit Ticket* - Option for where staff can click to access the backend of a ticket.

9. Status* - Based on the state of the ticket the ticket status can be 'Answered', 'Unanswered' or 'Solved'. Staff can use this to update status without reaching the ticket backend.

10. Assigned To* - The staff member to whom the ticket is assigned.

11. Created by - Time stamp when ticket was created. This is appended by the customer name who has creted the ticket.

12. Last reply -  Time stamp when last reply was added to the ticket. This is appended by the staff/customer name who has added the reply.

13. Subscribe* - Option for a staff member to get updates for the communication on the ticket. The assignee of a ticket by default is a subscriber to that ticket.

14. Ticket Products* - The offering/product for which customer has created a ticket.

15. Attachment - List of all the attachements that have been added to the replies, either by customer of staff.
#### C. Customer meta

16. Purchase History* - List of products, which a customer has either bought or has the order still under process. Order status is metnioned alongside the products name.

17. Ticket History - List of all the tickets that the customer has created in the Helpdesk. Order status is mentioned alngside the ticket title.
#### D. Ticket Reply Area
![Ticket reply area](https://cloud.githubusercontent.com/assets/9676513/6439933/217fb41e-c0fe-11e4-9401-42173fc20745.jpg)

18. WYSIWYG editor - Used by customer and staff to format their reply content. Real handly when staff wants to share code snippets.

19. Reply visibility - Used to determine visibility of a follow-up. 'Sensitive' type can be used by customer if he wants to send confidnetial information like server info.

20. Upload Files - Useb by customer and staff to add files to their follow-ups.

21. Add Follow-up Button - Button to add the follow-up to the ticket.

22. Keep Unanswered* - This option appears for staff to keep a ticket Unanswered, post reply.


### Ticket Visibility for a Customer

For a customer not all the features listed above will be visible. The items marked with asterisk are not visible to customers.

These marked featured are meant for the staff memebers, enabling them to get all the customer information at one place. This will reduce their number of visits to WordPress dashboard.
