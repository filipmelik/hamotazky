document.addEventListener("DOMContentLoaded", function() {
    
    if (TestStorage.testInProgressExists()) {
        loadTestInProgress();
    } else {
        TestStorage.clearTestAndProgress();
        TestStorage.saveTest(testContent);
    }

    document.querySelectorAll('.js-answer-row').forEach(answerRow => {
        answerRow.addEventListener('click', (e) => {
            e.preventDefault();
            let questionId = answerRow.dataset.questionId;
            let answerId = answerRow.dataset.answerId;
            selectAnswerForQuestionId(questionId, answerId);
            saveCurrentTestProgress()
        })
    });

    document.querySelector('.js-evaluate-button').addEventListener('click', () => {
        let firstUnansweredQuestionId = getIdOfFirstUnansweredQuestion();

        if (firstUnansweredQuestionId !== null) {
            // user did not answer all questions
            unansweredQuestionElement = document.querySelector(`[data-question-id="${firstUnansweredQuestionId}"]`)
            alert("Všechny otázky v testu nebyly zodpovězeny. Přesuneme vás nyní k nezodpovězené otázce.");
            unansweredQuestionElement.scrollIntoView();
        } else {
            dataToPost = {
                test: JSON.parse(testContent),
                answers: collectTestAnswers(),
            }
            postData(testEvaluationUrl, dataToPost);
        }
    });

    function postData(path, params) { 
        const hidden_form = document.createElement('form'); 
        hidden_form.method = 'post'; 
        hidden_form.action = path; 
          
        for (const key in params) { 
            if (params.hasOwnProperty(key)) { 
                const hidden_input = document.createElement 
                    ('input'); 
                hidden_input.type = 'hidden'; 
                hidden_input.name = key; 
                hidden_input.value = Base64.encode(JSON.stringify(params[key])); 

                hidden_form.appendChild(hidden_input); 
            } 
        } 

        document.body.appendChild(hidden_form); 
        hidden_form.submit(); 
    } 

    function getIdOfFirstUnansweredQuestion() {
        let firstUnansweredQuestionId = null;

        [...document.querySelectorAll('.js-question-box')].every(function(questionBoxElement, index) {
            let questionId = questionBoxElement.dataset.questionId;
            let selectedAnswerId = questionBoxElement.dataset.selectedAnswerId;

            if (!selectedAnswerId) {
                firstUnansweredQuestionId = questionId;
                return false;
            }
            return true;
        });

        return firstUnansweredQuestionId;
    }

    function collectTestAnswers() {
        var questions2answers = {}
        document.querySelectorAll('.js-question-box').forEach(function(questionBoxElement) {
            let questionId = questionBoxElement.dataset.questionId;
            let selectedAnswerId = questionBoxElement.dataset.selectedAnswerId;

            questions2answers[questionId] = selectedAnswerId;
        });

        return questions2answers;
    }

    function selectAnswerForQuestionId(questionId, answerId) {
        let unselectedItemClass = "list-group-item-secondary";
        let unselectedItemBullet = '🔵';
        let selectedItemClass = "list-group-item-info";
        let selectedItemBullet = '🤔';


        // deselect previous answer, if any
        let questionBoxElement = document.querySelector('.js-question-box-' + questionId);
        questionBoxElement.querySelectorAll('.js-answer-row').forEach(answerRow => {
            const bulletElement = answerRow.querySelector('.js-question-bullet');
            bulletElement.textContent = unselectedItemBullet;
            answerRow.classList.remove(selectedItemClass);
            answerRow.classList.add(unselectedItemClass);
        });

        // select answer row
        let answerRow = document.querySelector('.js-answer-row-' + answerId);
        answerRow.classList.remove(unselectedItemClass);
        const bulletElement = answerRow.querySelector('.js-question-bullet');
        bulletElement.animate({ opacity: [0, 1] }, { duration: 1000, iterations: 1, easing: "ease-out" })
            .onfinish = (e) => {
                 e.target.effect.target.style.opacity = 1;
            };

        answerRow.classList.add(selectedItemClass);
        bulletElement.textContent = selectedItemBullet;
        questionBoxElement.dataset.selectedAnswerId = answerRow.dataset.answerId;
    }

    function questionExistsInTest(questionId) {
        let questionBoxElement = document.querySelector('.js-question-box-' + questionId);
        return questionBoxElement !== null;
    }

    function saveCurrentTestProgress() {
        progress = new Object();

        document.querySelectorAll('.js-question-box').forEach(questionBox => {
            questionId = questionBox.dataset.questionId;
            selectedAnswerId = questionBox.dataset.selectedAnswerId;

            if (selectedAnswerId) {
                progress[questionId] = selectedAnswerId;
            }
        });

        TestStorage.saveTestProgress(progress)
    }

    function loadTestInProgress() {
        let progress = TestStorage.loadTestProgress();

        for (var questionId in progress) {            
            if (questionExistsInTest(questionId)) {
                var answerId = progress[questionId];
                selectAnswerForQuestionId(questionId, answerId);
            }
        }
    }

});

/**
*
*  Base64 encode / decode
*  http://www.webtoolkit.info
*
**/
var Base64 = {

    // private property
    _keyStr: "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/="

    // public method for encoding
    , encode: function (input)
    {
        var output = "";
        var chr1, chr2, chr3, enc1, enc2, enc3, enc4;
        var i = 0;

        input = Base64._utf8_encode(input);

        while (i < input.length)
        {
            chr1 = input.charCodeAt(i++);
            chr2 = input.charCodeAt(i++);
            chr3 = input.charCodeAt(i++);

            enc1 = chr1 >> 2;
            enc2 = ((chr1 & 3) << 4) | (chr2 >> 4);
            enc3 = ((chr2 & 15) << 2) | (chr3 >> 6);
            enc4 = chr3 & 63;

            if (isNaN(chr2))
            {
                enc3 = enc4 = 64;
            }
            else if (isNaN(chr3))
            {
                enc4 = 64;
            }

            output = output +
                this._keyStr.charAt(enc1) + this._keyStr.charAt(enc2) +
                this._keyStr.charAt(enc3) + this._keyStr.charAt(enc4);
        } // Whend 

        return output;
    } // End Function encode 

    // private method for UTF-8 encoding
    ,_utf8_encode: function (string)
    {
        var utftext = "";
        string = string.replace(/\r\n/g, "\n");

        for (var n = 0; n < string.length; n++)
        {
            var c = string.charCodeAt(n);

            if (c < 128)
            {
                utftext += String.fromCharCode(c);
            }
            else if ((c > 127) && (c < 2048))
            {
                utftext += String.fromCharCode((c >> 6) | 192);
                utftext += String.fromCharCode((c & 63) | 128);
            }
            else
            {
                utftext += String.fromCharCode((c >> 12) | 224);
                utftext += String.fromCharCode(((c >> 6) & 63) | 128);
                utftext += String.fromCharCode((c & 63) | 128);
            }

        } // Next n 

        return utftext;
    } // End Function _utf8_encode 
}
