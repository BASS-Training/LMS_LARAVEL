<?php                                                                                                                                                                                                   
                                                                                                                                                                                                          
  namespace Database\Seeders;                                                                                                                                                                             
                                                                                                                                                                                                          
  use Illuminate\Database\Seeder;                                                                                                                                                                         
  use App\Models\User;                                                                                                                                                                                    
  use App\Models\Course;                                                                                                                                                                                  
  use App\Models\Lesson;                                                                                                                                                                                  
  use App\Models\Content;                                                                                                                                                                                 
  use App\Models\Quiz;                                                                                                                                                                                    
  use App\Models\Question;                                                                                                                                                                                
  use App\Models\QuizAttempt;                                                                                                                                                                             
  use Illuminate\Support\Facades\DB;                                                                                                                                                                      
  use Illuminate\Support\Facades\Hash;                                                                                                                                                                    
  use Carbon\Carbon;                                                                                                                                                                                      
                                                                                                                                                                                                          
  class DemoProgressSeeder extends Seeder                                                                                                                                                                 
  {                                                                                                                                                                                                       
      public function run(): void                                                                                                                                                                         
      {                                                                                                                                                                                                   
          $now = Carbon::now();                                                                                                                                                                           
                                                                                                                                                                                                          
          // Users                                                                                                                                                                                        
          $admin = User::firstOrCreate(                                                                                                                                                                   
              ['email' => 'admin@example.com'],                                                                                                                                                           
              ['name' => 'Admin Demo', 'password' => Hash::make('password')]                                                                                                                              
          );                                                                                                                                                                                              
          $participant = User::firstOrCreate(                                                                                                                                                             
              ['email' => 'participant@example.com'],                                                                                                                                                     
              ['name' => 'Participant Demo', 'password' => Hash::make('password')]                                                                                                                        
          );                                                                                                                                                                                              
                                                                                                                                                                                                          
          // Course + lessons                                                                                                                                                                             
          $course = Course::firstOrCreate(                                                                                                                                                                
              ['title' => 'Demo Course'],                                                                                                                                                                 
              ['description' => 'Dummy data for progress screen', 'status' => 'published']                                                                                                                
          );                                                                                                                                                                                              
                                                                                                                                                                                                          
          $lesson1 = Lesson::firstOrCreate(                                                                                                                                                               
              ['course_id' => $course->id, 'order' => 1],                                                                                                                                                 
              ['title' => 'Lesson 1', 'description' => 'Intro']                                                                                                                                           
          );                                                                                                                                                                                              
          $lesson2 = Lesson::firstOrCreate(                                                                                                                                                               
              ['course_id' => $course->id, 'order' => 2],                                                                                                                                                 
              ['title' => 'Lesson 2', 'description' => 'Quiz time']                                                                                                                                       
          );                                                                                                                                                                                              
                                                                                                                                                                                                          
          // Contents                                                                                                                                                                                     
          $contentL1Video = Content::firstOrCreate(                                                                                                                                                       
              ['lesson_id' => $lesson1->id, 'order' => 1],                                                                                                                                                
              [                                                                                                                                                                                           
                  'title' => 'Video Intro',                                                                                                                                                               
                  'type' => 'video',                                                                                                                                                                      
                  'body' => 'https://example.com/video',                                                                                                                                                  
                  'is_optional' => false,                                                                                                                                                                 
              ]                                                                                                                                                                                           
          );                                                                                                                                                                                              
                                                                                                                                                                                                          
          $quiz = Quiz::firstOrCreate(                                                                                                                                                                    
              ['lesson_id' => $lesson2->id, 'title' => 'Quiz 1'],                                                                                                                                         
              [                                                                                                                                                                                           
                  'user_id' => $admin->id,                                                                                                                                                                
                  'passing_percentage' => 60,                                                                                                                                                             
                  'status' => 'published',                                                                                                                                                                
              ]                                                                                                                                                                                           
          );                                                                                                                                                                                              
                                                                                                                                                                                                          
          // Minimal questions to give scoring basis                                                                                                                                                      
          Question::firstOrCreate(                                                                                                                                                                        
              ['quiz_id' => $quiz->id, 'question_text' => 'Q1'],                                                                                                                                          
              ['type' => 'multiple_choice', 'marks' => 5]                                                                                                                                                 
          );                                                                                                                                                                                              
          Question::firstOrCreate(                                                                                                                                                                        
              ['quiz_id' => $quiz->id, 'question_text' => 'Q2'],                                                                                                                                          
              ['type' => 'multiple_choice', 'marks' => 5]                                                                                                                                                 
          );                                                                                                                                                                                              
                                                                                                                                                                                                          
          $contentL2Quiz = Content::firstOrCreate(                                                                                                                                                        
              ['lesson_id' => $lesson2->id, 'order' => 1],                                                                                                                                                
              [                                                                                                                                                                                           
                  'title' => 'Quiz Content',                                                                                                                                                              
                  'type' => 'quiz',                                                                                                                                                                       
                  'quiz_id' => $quiz->id,                                                                                                                                                                 
                  'is_optional' => false,                                                                                                                                                                 
              ]                                                                                                                                                                                           
          );                                                                                                                                                                                              
                                                                                                                                                                                                          
          // Enroll participant                                                                                                                                                                           
          $course->enrolledUsers()->syncWithoutDetaching([$participant->id]);                                                                                                                             
                                                                                                                                                                                                          
          // Mark content completion for lesson 1                                                                                                                                                         
          DB::table('content_user')->updateOrInsert(                                                                                                                                                      
              ['content_id' => $contentL1Video->id, 'user_id' => $participant->id],                                                                                                                       
              ['completed' => true, 'completed_at' => $now]                                                                                                                                               
          );                                                                                                                                                                                              
                                                                                                                                                                                                          
          // Quiz attempt to count as completed                                                                                                                                                           
          QuizAttempt::updateOrCreate(                                                                                                                                                                    
              ['quiz_id' => $quiz->id, 'user_id' => $participant->id],                                                                                                                                    
              [                                                                                                                                                                                           
                  'score' => 8, // out of 10 marks total                                                                                                                                                  
                  'passed' => true,                                                                                                                                                                       
                  'started_at' => $now->copy()->subMinutes(10),                                                                                                                                           
                  'completed_at' => $now->copy()->subMinutes(5),                                                                                                                                          
              ]                                                                                                                                                                                           
          );                                                                                                                                                                                              
                                                                                                                                                                                                          
          // Mark quiz content completion                                                                                                                                                                 
          DB::table('content_user')->updateOrInsert(                                                                                                                                                      
              ['content_id' => $contentL2Quiz->id, 'user_id' => $participant->id],                                                                                                                        
              ['completed' => true, 'completed_at' => $now]                                                                                                                                               
          );                                                                                                                                                                                              
                                                                                                                                                                                                          
        // Mark lesson completion in lesson_user
        DB::table('lesson_user')->updateOrInsert(
            ['lesson_id' => $lesson1->id, 'user_id' => $participant->id],
            ['completed' => true, 'completed_at' => $now]
        );
        DB::table('lesson_user')->updateOrInsert(
            ['lesson_id' => $lesson2->id, 'user_id' => $participant->id],
            ['completed' => true, 'completed_at' => $now]
        );
      }                                                                                                                                                                                                   
  }                                                
