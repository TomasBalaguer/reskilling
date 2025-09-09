#!/bin/bash

# Script para probar upload de respuestas de audio
# Reemplaza los paths con tus archivos de audio reales

curl -X POST http://localhost:8001/api/campaigns/nrysYjqe/questionnaires/2/responses \
  -H "Accept: application/json" \
  -F "respondent[name]=Juan Pérez" \
  -F "respondent[email]=juan.perez@example.com" \
  -F "respondent[age]=28" \
  -F "responses[q1][type]=audio" \
  -F "responses[q1][audio_duration]=45.2" \
  -F "responses[q2][type]=audio" \
  -F "responses[q2][audio_duration]=38.7" \
  -F "responses[q3][type]=audio" \
  -F "responses[q3][audio_duration]=52.1" \
  -F "responses[q4][type]=audio" \
  -F "responses[q4][audio_duration]=67.8" \
  -F "responses[q5][type]=audio" \
  -F "responses[q5][audio_duration]=41.3" \
  -F "responses[q6][type]=audio" \
  -F "responses[q6][audio_duration]=55.6" \
  -F "responses[q7][type]=audio" \
  -F "responses[q7][audio_duration]=72.4" \
  -F "audio_files[q1]=@/path/to/your/audio1.mp3" \
  -F "audio_files[q2]=@/path/to/your/audio2.mp3" \
  -F "audio_files[q3]=@/path/to/your/audio3.mp3" \
  -F "audio_files[q4]=@/path/to/your/audio4.mp3" \
  -F "audio_files[q5]=@/path/to/your/audio5.mp3" \
  -F "audio_files[q6]=@/path/to/your/audio6.mp3" \
  -F "audio_files[q7]=@/path/to/your/audio7.mp3"