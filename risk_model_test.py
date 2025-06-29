import risk_assessment
import pytest

def test_risk_prediction():
    test_data = {'gpa': 3.5, 'attendance': 90, 'prev_defaults': 0}
    result = risk_assessment.predict(test_data)  # Add predict() function to your script
    assert 0 <= result['risk_score'] <= 1