# ai/risk_assessment.py
import pandas as pd
from sklearn.ensemble import RandomForestClassifier
import joblib
import sys
import json
import os

MODEL_PATH = 'risk_model.joblib'

def train_model():
    """Train and save the initial model"""
    data = {
        'gpa': [3.8, 2.5, 3.2, 3.9, 2.1, 3.5, 3.7, 2.9],
        'attendance': [95, 70, 85, 98, 60, 88, 92, 75],
        'previous_loans': [1, 3, 2, 1, 4, 2, 1, 3],
        'repayment_prob': [1, 0, 1, 1, 0, 1, 1, 0]  # 1=Good, 0=Risk
    }
    df = pd.DataFrame(data)
    
    model = RandomForestClassifier()
    model.fit(df[['gpa', 'attendance', 'previous_loans']], df['repayment_prob'])
    joblib.dump(model, MODEL_PATH)

def predict(input_data):
    """Make prediction from input data"""
    if not os.path.exists(MODEL_PATH):
        train_model()
    
    model = joblib.load(MODEL_PATH)
    prediction = model.predict_proba([[input_data['gpa'], input_data['attendance'], input_data['previous_loans']]])
    return {'risk_score': float(prediction[0][1]), 'risk_category': 'High' if prediction[0][1] < 0.7 else 'Low'}

if __name__ == "__main__":
    # Command-line execution (for PHP calls)
    if len(sys.argv) > 1:
        input_data = json.loads(sys.argv[1])
        result = predict(input_data)
        print(json.dumps(result))