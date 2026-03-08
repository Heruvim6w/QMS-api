Feature: Authentication and Registration
  As a user
  I want to be able to register and login to the application
  So that I can access the secure features

  Scenario: User can register with valid credentials
    Given I have valid registration data:
      | field | value |
      | name | John Doe |
      | email | john@example.com |
      | password | SecurePass123! |
      | password_confirmation | SecurePass123! |
    When I send a POST request to "/api/v1/register"
    Then the response status code should be 201
    And the response should contain a JWT token
    And the user should be created in the database
    And the user should have a unique UIN assigned

  Scenario: User cannot register with duplicate email
    Given a user exists with email "john@example.com"
    And I have registration data with email "john@example.com"
    When I send a POST request to "/api/v1/register"
    Then the response status code should be 422
    And the response should contain validation error for email

  Scenario: User can login and receive confirmation email for new device
    Given a user exists with:
      | field | value |
      | email | john@example.com |
      | password | SecurePass123! |
    And I am on a new device
    When I send a POST request to "/api/v1/login" with credentials:
      | field | value |
      | login | john@example.com |
      | password | SecurePass123! |
    Then the response status code should be 200
    And the response should indicate confirmation is required
    And a login confirmation email should be sent
    And a login token should be created in the database

  Scenario: User cannot login from new device without confirming email
    Given a user exists with:
      | field | value |
      | email | john@example.com |
      | password | SecurePass123! |
    And a pending login confirmation token exists for this user
    When the user attempts to use the API without confirming
    Then the response status code should be 401

  Scenario: User can confirm login and receive JWT token
    Given a user exists
    And a valid login confirmation token exists for this user
    When I send a POST request to "/api/v1/login/confirm" with the token
    Then the response status code should be 200
    And the response should contain a JWT token
    And the login token should be marked as confirmed

  Scenario: User can login from known device without confirmation
    Given a user exists with email "john@example.com"
    And the user has a confirmed login session on this device
    When I send a POST request to "/api/v1/login" with credentials:
      | field | value |
      | login | john@example.com |
      | password | SecurePass123! |
    Then the response status code should be 200
    And the response should not require confirmation
    And the response should contain a JWT token

  Scenario: Confirmed sessions remain active after new login
    Given a user exists
    And the user has 2 active confirmed sessions
    When the user logs in from a new device
    Then the user should have 3 confirmed sessions
    And all previous sessions should still be active

  Scenario: User can login with UIN instead of email
    Given a user exists with UIN "12345678"
    And the user has a confirmed login on this device
    When I send a POST request to "/api/v1/login" with:
      | field | value |
      | login | 12345678 |
      | password | SecurePass123! |
    Then the response status code should be 200

Feature: Chat Management
  As a user
  I want to create and manage chats
  So that I can communicate with other users

  Scenario: User can create a private chat and send a message
    Given a user "alice" exists
    And a user "bob" exists
    When "alice" sends a message to "bob":
      | field | value |
      | content | Hello Bob! |
      | type | text |
    Then a private chat should be created between "alice" and "bob"
    And the message should be stored in the database
    And the message should be encrypted

  Scenario: User can create a group chat
    Given a user "alice" exists
    And a user "bob" exists
    And a user "charlie" exists
    When "alice" creates a group chat with name "Team Meeting" and includes "bob" and "charlie"
    Then a group chat should be created with 3 members
    And "alice" should be the creator
    And all members should be able to see the chat

  Scenario: Messages can be sent to existing chats
    Given a private chat exists between "alice" and "bob"
    When "alice" sends a message to the chat:
      | field | value |
      | content | This is a reply |
      | type | text |
    Then the message should be added to the chat
    And "bob" should be able to see the message

  Scenario: User can send messages to group chat
    Given a group chat exists with members "alice", "bob", and "charlie"
    When "alice" sends a message to the chat:
      | field | value |
      | content | Hello everyone! |
      | type | text |
    Then the message should be added to the chat
    And all members should be able to see the message

  Scenario: User cannot send message to chat they are not member of
    Given a chat exists with members "bob" and "charlie"
    And I am logged in as "alice"
    When I try to send a message to this chat
    Then the response status code should be 403

Feature: Localization
  As a user
  I want to set my preferred language
  So that the application displays content in my language

  Scenario: User locale is correctly detected
    Given a user exists with locale "en"
    When I retrieve the user profile
    Then the locale should be "en"

  Scenario: User can manually change their locale
    Given a user exists with locale "en"
    When I send a PUT request to "/api/v1/users/locale" with:
      | field | value |
      | locale | ru |
    Then the response status code should be 200
    And the user's locale should be updated to "ru"

  Scenario: Status list changes language when locale changes
    Given a user exists with locale "en"
    When I get the available statuses
    Then the status names should be in English
    When I change the user's locale to "ru"
    And I get the available statuses
    Then the status names should be in Russian

Feature: File Attachments
  As a user
  I want to attach files to messages
  So that I can share documents and images with others

  Scenario: User can attach a file to a message
    Given a chat exists with me as a member
    And I have sent a message
    When I upload a file "photo.jpg" to the message
    Then the file should be attached to the message
    And the attachment should have metadata stored

  Scenario: Multiple files can be attached to one message
    Given a chat exists with me as a member
    And I have sent a message
    When I upload "document.pdf" to the message
    And I upload "image.png" to the message
    Then the message should have 2 attachments

  Scenario: User can download an attachment
    Given a chat with a message containing an attachment exists
    When I request to download the attachment
    Then the file should be returned
    And the response should have the correct mime type

  Scenario: User cannot access files from chats they are not a member of
    Given a chat exists where I am not a member
    And the chat has a message with an attachment
    When I try to access the attachment
    Then the response status code should be 403

Feature: WebRTC Calls
  As a user
  I want to make audio and video calls
  So that I can communicate in real-time with other users

  Scenario: User can initiate a video call
    Given "alice" and "bob" are in a chat together
    When "alice" initiates a video call to "bob" with SDP offer
    Then the call should be created with status "ringing"
    And the call should have type "video"
    And the SDP offer should be stored

  Scenario: User can answer a call
    Given a ringing call exists from "alice" to "bob"
    When "bob" answers the call with SDP answer
    Then the call status should change to "active"
    And the SDP answer should be stored
    And the answered_at timestamp should be set

  Scenario: User can decline a call
    Given a ringing call exists from "alice" to "bob"
    When "bob" declines the call
    Then the call status should change to "declined"

  Scenario: User can end an active call
    Given an active call exists between "alice" and "bob"
    When "alice" ends the call
    Then the call status should change to "ended"
    And the ended_at timestamp should be set
    And the duration should be calculated

  Scenario: ICE candidates can be added to a call
    Given a ringing call exists
    When I add an ICE candidate to the call
    Then the candidate should be stored
    And the call should remain in the same state

  Scenario: User cannot answer a call that is not for them
    Given a ringing call from "alice" to "bob"
    And I am logged in as "charlie"
    When I try to answer the call
    Then the response status code should be 403

  Scenario: Audio calls can be initiated
    Given "alice" and "bob" are in a chat
    When "alice" initiates an audio call to "bob"
    Then the call should be created with type "audio"

  Scenario: User cannot answer an ended call
    Given an ended call from "alice" to "bob"
    When "bob" tries to answer the call
    Then the response status code should be 409

