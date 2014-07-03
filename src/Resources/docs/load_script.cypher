//NOTE: THIS SCRIPT WILL WORK AUTOMATICALLY WHENEVER NEO4J IMPLEMENTS MULTIPLE COMMANDS INPUT. FOR NOW, YOU HAVE TO COPY+PASTE THE COMMANDS 1 BY 1

//Define constraints
CREATE CONSTRAINT ON (u:USER)
ASSERT
u._username IS UNIQUE;

CREATE CONSTRAINT ON (q:QUESTION)
ASSERT
q._id IS UNIQUE;

CREATE CONSTRAINT ON (a:ANSWER)
ASSERT
a._id IS UNIQUE;

//Create users
CREATE
(u:USER {_username: "user1", _status: "active"});

CREATE
(u:USER {_username: "user2", _status: "active"});

CREATE
(u:USER {_username: "user3", _status: "active"});

//Create 5 questions
CREATE
(q:QUESTION {_id: "question1"}), 
(:ANSWER {_id: "answer1"})-[:IS_ANSWER_OF]->(q),
(:ANSWER {_id: "answer2"})-[:IS_ANSWER_OF]->(q),
(:ANSWER {_id: "answer3"})-[:IS_ANSWER_OF]->(q),
(:ANSWER {_id: "answer4"})-[:IS_ANSWER_OF]->(q);

CREATE
(q:QUESTION {_id: "question2"}), 
(:ANSWER {_id: "answer5"})-[:IS_ANSWER_OF]->(q),
(:ANSWER {_id: "answer6"})-[:IS_ANSWER_OF]->(q),
(:ANSWER {_id: "answer7"})-[:IS_ANSWER_OF]->(q),
(:ANSWER {_id: "answer8"})-[:IS_ANSWER_OF]->(q);

CREATE
(q:QUESTION {_id: "question3"}), 
(:ANSWER {_id: "answer9"})-[:IS_ANSWER_OF]->(q),
(:ANSWER {_id: "answer10"})-[:IS_ANSWER_OF]->(q),
(:ANSWER {_id: "answer11"})-[:IS_ANSWER_OF]->(q),
(:ANSWER {_id: "answer12"})-[:IS_ANSWER_OF]->(q);

CREATE
(q:QUESTION {_id: "question4"}), 
(:ANSWER {_id: "answer13"})-[:IS_ANSWER_OF]->(q),
(:ANSWER {_id: "answer14"})-[:IS_ANSWER_OF]->(q),
(:ANSWER {_id: "answer15"})-[:IS_ANSWER_OF]->(q),
(:ANSWER {_id: "answer16"})-[:IS_ANSWER_OF]->(q);

CREATE
(q:QUESTION {_id: "question5"}), 
(:ANSWER {_id: "answer17"})-[:IS_ANSWER_OF]->(q),
(:ANSWER {_id: "answer18"})-[:IS_ANSWER_OF]->(q),
(:ANSWER {_id: "answer19"})-[:IS_ANSWER_OF]->(q),
(:ANSWER {_id: "answer20"})-[:IS_ANSWER_OF]->(q);

//user1 and user2 answer question1
MATCH
(user:USER {_username: "user1"}), 
(answered:ANSWER {_id: "answer1"}),
(accepted1:ANSWER {_id: "answer1"}),
(accepted2:ANSWER {_id: "answer2"}),
(answered) -[:IS_ANSWER_OF]-> (question)
CREATE
(user)-[:ANSWERS]->(answered),
(user)-[:ACCEPTS]->(accepted1),
(user)-[:ACCEPTS]->(accepted2),
(user)-[:RATES {_rating: 1}]->(question);

MATCH
(user:USER {_username: "user2"}), 
(answered:ANSWER {_id: "answer2"}),
(accepted1:ANSWER {_id: "answer1"}),
(accepted2:ANSWER {_id: "answer2"}),
(answered) -[:IS_ANSWER_OF]-> (question)
CREATE
(user)-[:ANSWERS]->(answered),
(user)-[:ACCEPTS]->(accepted1),
(user)-[:ACCEPTS]->(accepted2),
(user)-[:RATES {_rating: 50}]->(question);

//user1 and user2 answer question2
MATCH
(user:USER {_username: "user1"}), 
(answered:ANSWER {_id: "answer6"}),
(accepted1:ANSWER {_id: "answer6"}),
(accepted2:ANSWER {_id: "answer7"}),
(answered) -[:IS_ANSWER_OF]-> (question)
CREATE
(user)-[:ANSWERS]->(answered),
(user)-[:ACCEPTS]->(accepted1),
(user)-[:ACCEPTS]->(accepted2),
(user)-[:RATES {_rating: 10}]->(question);

MATCH
(user:USER {_username: "user2"}), 
(answered:ANSWER {_id: "answer5"}),
(accepted1:ANSWER {_id: "answer5"}),
(accepted2:ANSWER {_id: "answer6"}),
(accepted3:ANSWER {_id: "answer7"}),
(accepted4:ANSWER {_id: "answer8"}),
(answered) -[:IS_ANSWER_OF]-> (question)
CREATE
(user)-[:ANSWERS]->(answered),
(user)-[:ACCEPTS]->(accepted1),
(user)-[:ACCEPTS]->(accepted2),
(user)-[:ACCEPTS]->(accepted3),
(user)-[:ACCEPTS]->(accepted4),
(user)-[:RATES {_rating: 0}]->(question);

//only user1 answers question3
MATCH
(user:USER {_username: "user1"}), 
(answered:ANSWER {_id: "answer10"}),
(accepted1:ANSWER {_id: "answer9"}),
(accepted2:ANSWER {_id: "answer10"}),
(accepted3:ANSWER {_id: "answer11"}),
(accepted4:ANSWER {_id: "answer12"}),
(answered) -[:IS_ANSWER_OF]-> (question)
CREATE
(user)-[:ANSWERS]->(answered),
(user)-[:ACCEPTS]->(accepted1),
(user)-[:ACCEPTS]->(accepted2),
(user)-[:ACCEPTS]->(accepted3),
(user)-[:ACCEPTS]->(accepted4),
(user)-[:RATES {_rating: 0}]->(question);

//only user2 answers question4
MATCH
(user:USER {_username: "user2"}), 
(answered:ANSWER {_id: "answer15"}),
(accepted1:ANSWER {_id: "answer13"}),
(accepted2:ANSWER {_id: "answer16"}),
(answered) -[:IS_ANSWER_OF]-> (question)
CREATE
(user)-[:ANSWERS]->(answered),
(user)-[:ACCEPTS]->(accepted1),
(user)-[:ACCEPTS]->(accepted2),
(user)-[:RATES {_rating: 1}]->(question);

//note: question5 keeps unanswered
