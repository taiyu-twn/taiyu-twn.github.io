// Initialize firebase
import Firebase from 'firebase/app'
import 'firebase/database'
import 'firebase/auth'

const FirebaseApp = Firebase.initializeApp({
  apiKey: 'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA',
  authDomain: 'velotropolis.firebaseapp.com',
  databaseURL: 'https://velotropolis.firebaseio.com',
  projectId: 'velotropolis',
  storageBucket: 'velotropolis.appspot.com',
  messagingSenderId: '00000000000',
})

// Firebase Realtime Database
const db = FirebaseApp.database()

export { db }
