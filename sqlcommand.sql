SELECT * FROM mdl_question_answers;
SELECT * FROM mdl_question_attempts;
SELECT * FROM mdl_question;
SELECT * FROM mdl_quiz_attempts;
SELECT * FROM mdl_question_categories;
SELECT 
	mdl_question_attempts.id as id,
	mdl_question_attempts.questionusageid as quizattemptid,
    mdl_quiz_attempts.quiz as quizid,
    mdl_quiz_attempts.userid as userid,
    mdl_question_attempts.questionid as questionid,
    mdl_question.category as categoryid,
    mdl_question_categories.name as category,
    IF(mdl_question_attempts.rightanswer = mdl_question_attempts.responsesummary, mdl_question_attempts.maxmark, mdl_question_attempts.minfraction) as grade,
	mdl_question_attempts.maxmark as maxgrade,
    mdl_question_attempts.responsesummary as response,
    mdl_question_attempts.rightanswer as rightanswer
 FROM mdl_question_attempts
 INNER JOIN mdl_quiz_attempts on mdl_question_attempts.questionusageid= mdl_quiz_attempts.id
 INNER JOIN mdl_question on mdl_question.id = mdl_question_attempts.questionid
 INNER JOIN mdl_question_categories on mdl_question_categories.id = mdl_question.category;
-- For each question attempt with userid and questionusageid=quizattemptid, if rightanswer = responsesummary give maxmark, otherwise give 0, only works for full credit/no credit questions.

CREATE TABLE mdl_question_grades AS SELECT 
	mdl_question_attempts.id as id,
	mdl_question_attempts.questionusageid as quizattemptid,
    mdl_quiz_attempts.quiz as quizid,
    mdl_quiz_attempts.userid as userid,
    mdl_question_attempts.questionid as questionid,
    mdl_question.category as categoryid,
    mdl_question_categories.name as category,
    IF(mdl_question_attempts.rightanswer = mdl_question_attempts.responsesummary, mdl_question_attempts.maxmark, mdl_question_attempts.minfraction) as grade,
	mdl_question_attempts.maxmark as maxgrade,
    mdl_question_attempts.responsesummary as response,
    mdl_question_attempts.rightanswer as rightanswer
 FROM mdl_question_attempts
 INNER JOIN mdl_quiz_attempts on mdl_question_attempts.questionusageid= mdl_quiz_attempts.id
 INNER JOIN mdl_question on mdl_question.id = mdl_question_attempts.questionid
 INNER JOIN mdl_question_categories on mdl_question_categories.id = mdl_question.category;