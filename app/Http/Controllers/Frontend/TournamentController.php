<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tournament;
use App\Models\Team;
use App\Models\TournamentImage;
use App\Models\TournamentCategory;
use App\Models\Category;
use App\Models\TournamentType;
use App\Models\EventType;
use App\Models\Country;
use App\Models\NumberOfTeam;
use App\Models\TournamentFormat;
use App\Models\TournamentLevel;
use App\Models\User;
use App\Models\TournamentRounds;
use App\Models\TournamentMatches;
use App\Http\Requests\StoreTournamentRequest;
use Stripe\StripeClient;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Models\Wishlist;
use App\Models\Tournament_matches_schedule;

class TournamentController extends Controller
{
    public function getCategories(Request $request){
        $search = $request->search;
        $categories = Category::where('name', 'like', '%'.$search.'%')->get();
        return response()->json(['data' => $categories], 200);
    }
    public function tournamentsByUser(Request $request){
        
        $tournaments = Tournament::with([
            'images', 
            'banners', 
            'tournamentCategories',
            'category',
            'teams.teamMembers',
            'reviews.user',
            'tournamentType',
            ])
        ->where('is_active', 1)
        ->where('user_id', $request->user_id)
        ->latest()
        ->paginate(10);
        return response()->json(['data' => $tournaments], 200);
    }
    public function tournamentDetail(Request $request){
        if(!empty($request->id) && intval($request->id) > 0 &&  $tournament = Tournament::with([
            'images', 
            'tournamentCategories',
            'category',
            'teams.teamMembers',
            'reviews.user',
            'tournamentType',
            ])
        ->where('is_active', 1)
        ->where('id', $request->id)
        ->first()){

            // print_r($tournament->tournament_type);die;
            $tournament->second_match_breaks_val=$tournament->second_match_breaks;
            $tournament->schedule_date=!empty($tournament->schedule_date) ? date('Y-m-d',strtotime($tournament->schedule_date)) : "";
            $tournament->second_match_date=!empty($tournament->second_match_date) ? date('Y-m-d',strtotime($tournament->second_match_date)) : "";
            $tournament->third_match_date=!empty($tournament->third_match_date) ? date('Y-m-d',strtotime($tournament->third_match_date)) : "";
            $tournament->fourth_match_date=!empty($tournament->fourth_match_date) ? date('Y-m-d',strtotime($tournament->fourth_match_date)) : "";
            $tournament->schedule_breaks=!empty($tournament->schedule_breaks) ? floatval($tournament->schedule_breaks) : 0;
            $tournament->second_match_breaks=!empty($tournament->second_match_breaks) ? floatval($tournament->second_match_breaks) : 0;
            $tournament->third_match_breaks=!empty($tournament->third_match_breaks) ? floatval($tournament->third_match_breaks) : 0;
            $tournament->fourth_match_breaks=!empty($tournament->fourth_match_breaks) ? floatval($tournament->fourth_match_breaks) : 0;
            $tournament->logos_arr=$tournament->logos;
            $tournament->documents_arr=$tournament->documents;
            $tournament->banner_arr=$tournament->banners;
            if(date('Y-m-d',strtotime($tournament->start_date)) > date('Y-m-d')){
                $tournament->allow_edit=1;
                $tournament->allow_withdraw=1;
            }
            else{
                $tournament->allow_withdraw=0;
            }
            if($tournament->teams){
                $teamsCount = $tournament->teams->count();
            }
            $tournament->staff_arr=DB::table('tournament_staff')->where('tournament_id',$tournament->id)->get();
            $matches=array();
            foreach($tournament->macthes_schedule as $match){
                $matchObj=(Object)[];
                $matchObj->schedule_date=$match->schedule_date;
                $matchObj->schedule_time=$match->schedule_time;
                $matchObj->schedule_breaks=$match->schedule_breaks;
                $matchObj->venue_availability=$match->venue_availability;
                $matches[]=$matchObj;
            }
            $tournament->matches=$matches;
            $staffArr=array();
            foreach($tournament->staff_arr as $staff_obj){
                $staff=(Object)[];
                $staff->contact=$staff_obj->contact;
                $staff->responsibility=$staff_obj->responsibility;
                $staffArr[]=$staff;
            }
            $tournament->staffArr=$staffArr;
            if($tournament->inProgressRound){
                $tournament->in_progress_round = $tournament->inProgressRound;
                if($tournament->in_progress_round->matches){
                    foreach($tournament->in_progress_round->matches as $in_progress_match_row){
                        if(empty($in_progress_match_row->team1) && empty($in_progress_match_row->team2)){
                            $tournament->start_next_round=1;
                        }
                    }
                }
            }
            // $tournament->countries=Country::all();
            
            if($tournament->match_type=='single'){
                $tournament->rounds = $tournament->rounds;
                foreach($tournament->rounds as $round){
                    $round->matches=$round->matches;
                    foreach($round->matches as $match){
                        $match->team_1=$match->team_1;
                        $match->team_2=$match->team_2;
                        $match->winner_row=$match->winner_row;
                        $match->looser_row=$match->looser_row;
                    }
                }
                $latestCompletedRound = TournamentRounds::where('tournament_id', $tournament->id)
                    ->where('status', 'completed')
                    ->latest()
                    ->first();
                
                
                 $pendingMatchesArr=[];
                if($latestCompletedRound){
                    $latestCompletedRound->matches=$latestCompletedRound->matches;
                        foreach($latestCompletedRound->matches as $latestCompletedMatch){
                            $latestCompletedMatch->team_1=$latestCompletedMatch->team_1;
                            $latestCompletedMatch->team_2=$latestCompletedMatch->team_2;
                            $latestCompletedMatch->winner_row=$latestCompletedMatch->winner_row;
                            $latestCompletedMatch->looser_row=$latestCompletedMatch->looser_row;
                        }
                    $completedMatches=TournamentMatches::where(['round_id'=>$latestCompletedRound->id,'status'=>1])->get();
                    if(count($completedMatches) > 0){
                        foreach($completedMatches as $completedMatche){
                            $pendingMatchesArr[]=$completedMatche->winner;
                        }
                    }
                }
                if(!empty($tournament->available_teams)){
                        if (strpos($tournament->available_teams, ',') !== false) {
                            $availableTeamsArr = explode(',', $tournament->available_teams);
                            $pendingMatchesArr=array_merge($pendingMatchesArr, $availableTeamsArr);
                        } else {
                            $pendingMatchesArr[]=$tournament->available_teams;
                        }
                }
                $tournament->pending_teams=$pendingMatchesArr;
                $tournament->latestCompletedRound=$latestCompletedRound;
                if(!empty($tournament->winners_pool)){
                    
                    if($this->isCommaSeparated($tournament->winners_pool)){
                        $winnersPoolArr=explode(",",$tournament->winners_pool);
                    }
                    else{
                        $winnersPoolArr=array();
                        $winnersPoolArr[]=$tournament->winners_pool;
                    }
                    $tournament->available_teams_count=count($winnersPoolArr);
                }
                $pending_match_teams_array=array();
                if(!empty($tournament->pending_match_teams)){
                    if($this->isCommaSeparated($tournament->pending_match_teams)){
                        $pending_match_teams_array=explode(",",$tournament->pending_match_teams);
                    }
                    else{
                        $pending_match_teams_array[]=$tournament->pending_match_teams;
                    }  
                }
                
                $tournament->pending_teams_available_for_match=count($pending_match_teams_array);
            }
            else if($tournament->match_type=='double'){
                $eleminated_teams_arr=array();
                if(!empty($tournament->eleminated_pool)){
                    $eleminated_pool_array=array();
                    if($this->isCommaSeparated($tournament->eleminated_pool)){
                        $eleminated_pool_array=explode(",",$tournament->eleminated_pool);
                    }
                    else{
                        $eleminated_pool_array[]=$tournament->eleminated_pool;
                    }
                    
                    foreach($eleminated_pool_array as $eleminated_pool_obj){
                        $teamObj=(Object)[];
                        $eleminated_teams_arr[]=Team::where('id',intval($eleminated_pool_obj))->get()->first();
                    }
                    $tournament->eleminated_teams_arr=$eleminated_teams_arr;
                }
                if($final_round = TournamentRounds::where('tournament_id', $tournament->id)
                    ->where('status', 'in_progress')
                    ->where('team_type', 'final')
                    ->latest()
                    ->first()){
                    $final_round->matches=$final_round->matches;
                    foreach($final_round->matches as $final_round_match){
                        $final_round_match->team_1=$final_round_match->team_1;
                        $final_round_match->team_2=$final_round_match->team_2;
                        $final_round_match->winner_row=$final_round_match->winner_row;
                        $final_round_match->looser_row=$final_round_match->looser_row;
                    }
                    $tournament->final_match_round_obj=$final_round; 
                }
                else if($final_completed_round = TournamentRounds::where('tournament_id', $tournament->id)
                    ->where('status', 'completed')
                    ->where('team_type', 'final')
                    ->latest()
                    ->first()){
                    $final_completed_round->matches=$final_completed_round->matches;
                    foreach($final_completed_round->matches as $final_round_match){
                        $final_round_match->team_1=$final_round_match->team_1;
                        $final_round_match->team_2=$final_round_match->team_2;
                        $final_round_match->winner_row=$final_round_match->winner_row;
                        $final_round_match->looser_row=$final_round_match->looser_row;
                    }
                    $tournament->final_completed_round=$final_completed_round; 
                }
                $tournament->completed_rounds = TournamentRounds::where('tournament_id', $tournament->id)
                    ->where('status', 'completed')
                    ->count();
                if($tournament->completed_rounds < 1){
                    $tournament->rounds = $tournament->rounds;
                    foreach($tournament->rounds as $round){
                        $round->matches=$round->matches;
                        foreach($round->matches as $match){
                            $match->team_1=$match->team_1;
                            $match->team_2=$match->team_2;
                            $match->winner_row=$match->winner_row;
                            $match->looser_row=$match->looser_row;
                        }
                    }
                     $latestCompletedRound = TournamentRounds::where('tournament_id', $tournament->id)
                        ->where('status', 'completed')
                        ->latest()
                        ->first();
                    
                     $pendingMatchesArr=[];
                    if($latestCompletedRound){
                        $latestCompletedRound->matches=$latestCompletedRound->matches;
                        foreach($latestCompletedRound->matches as $latestCompletedMatch){
                            $latestCompletedMatch->team_1=$latestCompletedMatch->team_1;
                            $latestCompletedMatch->team_2=$latestCompletedMatch->team_2;
                            $latestCompletedMatch->winner_row=$latestCompletedMatch->winner_row;
                            $latestCompletedMatch->looser_row=$latestCompletedMatch->looser_row;
                        }
                        $completedMatches=TournamentMatches::where(['round_id'=>$latestCompletedRound->id,'status'=>1])->get();
                        if(count($completedMatches) > 0){
                            foreach($completedMatches as $completedMatche){
                                $pendingMatchesArr[]=$completedMatche->winner;
                            }
                        }
                    }
                    if(!empty($tournament->available_teams)){
                            if (strpos($tournament->available_teams, ',') !== false) {
                                $availableTeamsArr = explode(',', $tournament->available_teams);
                                $pendingMatchesArr=array_merge($pendingMatchesArr, $availableTeamsArr);
                            } else {
                                $pendingMatchesArr[]=$tournament->available_teams;
                            }
                    }
                    $tournament->pending_teams=$pendingMatchesArr;
                    $tournament->latestCompletedRound=$latestCompletedRound;
                }
                else if($tournament->completed_rounds>= 1){
                    $winners_arr=array();
                    $loosers_arr=array();
                    $loosers_matches_arr=array();
                    $pendingLoosersMatchesArr=[];
                    $tournament->rounds = $tournament->rounds;

                    $looser_pool_arr=array();
                    if($this->isCommaSeparated($tournament->looser_pool)){
                        $looser_pool_arr=explode(",",$tournament->looser_pool);
                    }
                    else{
                        $looser_pool_arr[]=$tournament->looser_pool;
                    }

                    foreach($tournament->rounds as $round){
                        $winObject=(Object)[];
                        if($round->team_type=='win'){
                            
                            $winObject->id=$round->id;
                            $winObject->round_no=$round->round_no;
                            $winObject->status=$round->status; 
                            $winObject->matches=$round->matches;
                            $winObject->team_type=$round->team_type;
                            foreach($winObject->matches as $winMatch){
                                $winMatch->winner_row=$winMatch->winner_row;
                            }
                            $winners_arr[]=$winObject;
                        }
                        $loseObject=(Object)[];
                        if($round->team_type=='lose'){
                            
                            $loseObject->id=$round->id;
                            $loseObject->round_no=$round->round_no;
                            $loseObject->status=$round->status;
                            $loseObject->team_type=$round->team_type;
                            $loseObject->matches=$round->matches;

                            foreach($loseObject->matches as $looseMatch){
                                $looseMatch->looser_row=$looseMatch->looser_row;
                            }
                            $loosers_arr[]=$loseObject;
                        }

                        $round->matches=$round->matches;
                        
                        
                        foreach($round->matches as $match){
                            $match->team_1=$match->team_1;
                            $match->team_2=$match->team_2;
                            $match->winner_row=$match->winner_row;
                            $match->looser_row=$match->looser_row;
                            if(!empty($match->looser) && $match->looser > 0){
                                $pendingLoosersMatchesArr[]=$match->looser;
                                if(in_array($match->looser, $looser_pool_arr)){
                                    $loosers_matches_arr[]=$match->looser_row;
                                }
                                
                            }
                            
                        }
                        
                        
                        

                    }
                    $latestLooserInProgressRound=(Object)[];
                    if($latestLooserInProgressRound = TournamentRounds::where('tournament_id', $tournament->id)
                        ->where('status', 'in_progress')->where('team_type','lose')
                        ->latest()
                        ->first()){
                        $latestLooserInProgressRound->matches=$latestLooserInProgressRound->matches;
                        foreach($latestLooserInProgressRound->matches as $loose_match){
                            $loose_match->team_1=$loose_match->team_1;
                            $loose_match->team_2=$loose_match->team_2;
                            $loose_match->winner_row=$loose_match->winner_row;
                            $loose_match->looser_row=$loose_match->looser_row;
                        }
                        //....
                        $idsToRemove = array_merge(
                            $latestLooserInProgressRound->matches->pluck('team1')->all(),
                            $latestLooserInProgressRound->matches->pluck('team2')->all()
                        );

                        // Remove objects from the first array based on matching IDs
                        $filteredArray = array_filter($loosers_matches_arr, function ($item) use ($idsToRemove) {
                            return !in_array($item->id, $idsToRemove);
                        });

                        // Convert the associative array to an indexed array
                        $filteredArray = array_values($filteredArray);
                        $loosers_matches_arr=$filteredArray;
                    }
                    $tournament->loose_in_progress_round=$latestLooserInProgressRound;

                    $tournament->winners_arr=$winners_arr;
                    $tournament->loosers_arr=$loosers_arr;
                    $tournament->loosers_matches_arr=$loosers_matches_arr;


                    $latestCompletedRound = TournamentRounds::where('tournament_id', $tournament->id)
                        ->where('status', 'completed')
                        ->latest()
                        ->first();
                    
                     $pendingMatchesArr=[];
                    if($latestCompletedRound){
                        $latestCompletedRound->matches=$latestCompletedRound->matches;
                        foreach($latestCompletedRound->matches as $latestCompletedMatch){
                            $latestCompletedMatch->team_1=$latestCompletedMatch->team_1;
                            $latestCompletedMatch->team_2=$latestCompletedMatch->team_2;
                            $latestCompletedMatch->winner_row=$latestCompletedMatch->winner_row;
                            $latestCompletedMatch->looser_row=$latestCompletedMatch->looser_row;
                        }
                        $completedMatches=TournamentMatches::where(['round_id'=>$latestCompletedRound->id,'status'=>1])->get();
                        if(count($completedMatches) > 0){
                            foreach($completedMatches as $completedMatche){
                                $pendingMatchesArr[]=$completedMatche->winner;
                            }
                        }
                    }
                    if(!empty($tournament->winners_pool)){
                            if (strpos($tournament->winners_pool, ',') !== false) {
                                $availableTeamsArr = explode(',', $tournament->winners_pool);
                                $pendingMatchesArr=array_merge($pendingMatchesArr, $availableTeamsArr);
                            } else {
                                if (!in_array(intval($tournament->winners_pool), $pendingMatchesArr)) {
                                    $pendingMatchesArr[]=$tournament->winners_pool;
                                }
                            }
                    }
                    $tournament->pending_winner_teams=$pendingMatchesArr;
                    $tournament->pending_looser_teams=$pendingLoosersMatchesArr;
                    $loosing_teams=0;
                    if(!empty($tournament->looser_pool)){
                        if($this->isCommaSeparated($tournament->looser_pool)){
                            $loosing_teams=explode(",",$tournament->looser_pool);
                            $loosing_teams=count($loosing_teams);
                        }
                    }
                    if(!empty($tournament->eleminated_pool) && !empty($tournament->looser_pool) && !empty($tournament->winners_pool)){
                        $eleminated_pool_array=array();
                        $looser_pool_array=array();
                        $winners_pool_array=array();
                        if($this->isCommaSeparated($tournament->eleminated_pool)){
                            $eleminated_pool_array=explode(",",$tournament->eleminated_pool);
                        }
                        else{
                            $eleminated_pool_array[]=$tournament->eleminated_pool;
                        }
                        if($this->isCommaSeparated($tournament->looser_pool)){
                            $looser_pool_array=explode(",",$tournament->looser_pool);
                        }
                        else{
                            $looser_pool_array[]=$tournament->looser_pool;
                        }
                        if($this->isCommaSeparated($tournament->winners_pool)){
                            $winners_pool_array=explode(",",$tournament->winners_pool);
                        }
                        else{
                            $winners_pool_array[]=$tournament->winners_pool;
                        }
                        if(count($winners_pool_array)==1 && count($looser_pool_array)==1){
                            $tournament->final_match_round=1;
                        }
                        
                        
                    }
                    
                    $tournament->pending_looser_pool=$loosing_teams;
                    $tournament->latestCompletedRound=$latestCompletedRound;
                }
            }
            if($tournament->match_type=='single'){
                $single_brackets=array();
                $match_rounds=$tournament->descRounds;
                foreach($match_rounds as $r_index=>$match_round){
                    if($match_round->round_no!=1){
                        $match_round->matches=$match_round->matches;
                        foreach($match_round->matches as $m_key=>$match){
                            $match_round_obj=(Object)[];
                            $match_round_obj->id=$match->id;
                            $match_round_obj->nextMatchId=$match->next_match_id;
                            
                            $match_round_obj->tournamentRoundText=strval($match_round->round_no);
                            if($match->status==1){
                                $match_round_obj->state='PLAYED';
                            }
                            else if(!empty($match->team1) && !empty($match->team2)){
                                $match_round_obj->state='RUNNING';
                            }
                            else{
                                $match_round_obj->state='NO_SHOW';
                            }

                            
                            $participants=array();
                            
                            $team_a=(Object)[];
                            if(!empty($match->team1)){
                                $team_a->id=$match->team_1->id;
                                if(!empty($match->winner)){
                                    $team_a->resultText=$match->team1==$match->winner ? 'Won' : 'Lost';
                                }
                                else{
                                    $team_a->resultText=null;
                                }
                                $team_a->score=$match->team1_score;
                                $team_a->isWinner=$match->team1==$match->winner ? true : false;
                                $team_a->status=$match->status==1 ? "PLAYED" : null;
                                $team_a->name=$match->team_1->team_name." (".$match->team1_score.")";;
                                $team_a->picture=url('/storage/'.$match->team_1->logo); 
                            }
                            else{
                                $team_a->id=0;
                                $team_a->resultText=null;
                                $team_a->isWinner=false;
                                $team_a->status=$match->status==1 ? "PLAYED" : null;
                                $team_a->name='TBD';
                                $team_a->picture=null; 
                            }
                            
                            $participants[]=$team_a;

                            $team_b=(Object)[];
                            if(!empty($match->team2)){
                                $team_b->id=$match->team_2->id;
                                if(!empty($match->winner)){
                                    $team_b->resultText=$match->team2==$match->winner ? 'Won' : 'Lost';
                                }
                                else{
                                    $team_b->resultText=null;
                                }
                                
                                $team_b->isWinner=$match->team2==$match->winner ? true : false;
                                $team_b->status=$match->status==1 ? "PLAYED" : null;
                                $team_b->name=$match->team_2->team_name." (".$match->team2_score.")";
                                $team_b->picture=url('/storage/'.$match->team_2->logo);
                                 
                            }
                            else{
                                $team_b->id=0;
                                $team_b->resultText=null;
                                $team_b->isWinner=false;
                                $team_b->status=$match->status==1 ? "PLAYED" : null;
                                $team_b->name='TBD';
                                $team_b->picture=null; 
                            }
                            $participants[]=$team_b; 
                            

                            $match_round_obj->participants=$participants;

                            $single_brackets[]=$match_round_obj;
                        } 
                    }
                    
                }
                $tournament->single_brackets=$single_brackets;
                if($tournament->firstRound){
                    $tournament->firstRound=$tournament->firstRound;
                    $tournament->firstRound->matches=$tournament->firstRound->matches;
                    foreach($tournament->firstRound->matches as $m_key=>$match){
                        $match->team_1=$match->team_1;
                        $match->team_2=$match->team_2;
                        $match->winner_row=$match->winner_row;
                    }
                }
                
            }
            $tournament->tournament_type_val=$tournament->tournament_type;
            return response()->json([
                'data' => $tournament,
                'teamsCount' => $teamsCount,
                'acceptedTeamsCount'=>Team::where('status', 'accepted')->where('tournament_id',$tournament->id)->count(),
                'status'=>1
            ], 200);
        }
        else{
            return response()->json([
                'data' => null,
                'teamsCount' => null,
                'status'=>0,
                'request'=>$request->id
            ], 200);
        }
       
        
    }
    public function tournamentRoundDetail(Request $request){
        
        $tournament = Tournament::with([
            'images', 
            'tournamentCategories',
            'category',
            'teams.teamMembers',
            'reviews.user',
            'tournamentType',
            ])
        ->where('is_active', 1)
        ->where('id', $request->id)
        ->first();

        $teamsCount = $tournament->teams->count();
        if($round=TournamentRounds::where(['tournament_id'=>$tournament->id,'id'=>$request->round_id])->get()->first()){
            $round->matches=$round->matches;
            foreach($round->matches as $match){
                $match->team_1=$match->team_1;
                $match->team_2=$match->team_2;
                $match->winner_row=$match->winner_row;
            }
            $tournament->round = $round;
        }

        
        // foreach($tournament->rounds as $round){
            
        // }
        return response()->json([
            'data' => $tournament,
            'teamsCount' => $teamsCount,
            'round_id' => $request->round_id,
            'acceptedTeamsCount'=>Team::where('status', 'accepted')->where('tournament_id',$tournament->id)->count()
        ], 200);
    }
    public function getDetails(){
        $categories = Category::all();
        $tournamentTypes = TournamentType::all();
        $eventTyeps = EventType::all();
        $countries = Country::all();
        $numberOfTeams = NumberOfTeam::orderBy('number_of_teams')->get();
        $tournamentFormats = TournamentFormat::all();
        $tournamentLevels = TournamentLevel::all();
        return response()->json([
            'categories' => $categories,
            'tournamentTypes' => $tournamentTypes,
            'eventTyeps' => $eventTyeps,
            'countries' => $countries,
            'numberOfTeams' => $numberOfTeams,
            'tournamentFormats' => $tournamentFormats,
            'tournamentLevels' => $tournamentLevels,
            'tournament_fee' => !empty(DB::table('site_settings')->where('key', 'tournament_fee')->first()) ? DB::table('site_settings')->where('key', 'tournament_fee')->first()->value ? DB::table('site_settings')->where('key', 'tournament_fee')->first()->value : 0 : 0,
        ], 200);
    }

    public function getAll(Request $request){
        $sort = 'desc';
        if($request->has('sort')){
            $sort = $request->sort;
        }
        $tournaments = Tournament::with(['images', 'tournamentCategories','category'])
            ->where('is_active', 1)
            ->orderBy('id', $sort)
            ->latest()
            ->paginate(10);
            $category=$request->input('category', null);
            $name=$request->input('name', null);
            $input=$request->all();
            $tournaments_query = Tournament::with(['images', 'tournamentCategories','category','banners'])->where('is_active', 1)
                ->whereHas('category', function($query) use ($category){
                    if (!empty($category) && $category!=null && $category!='null') {
                        $query->where('name', $category);
                    }
                })->where(function($query) use ($name)
                    {
                        if (!empty($name) && $name!=null && $name!='null') {
                            $query->where('title','like', '%'.$name.'%');
                        }
                    });
                $tournaments=$tournaments_query->orderBy('id', $sort)->paginate(10);
            $p_sql = Str::replaceArray('?', $tournaments_query->getBindings(), $tournaments_query->toSql());
        foreach($tournaments as $tournament){
            $tournament->is_favorite=Wishlist::where('tournament_id', $tournament->id)->count();
        }
        return response()->json(['data' => $tournaments,'categories'=>Category::where('status','active')->orderBy('created_at', 'asc')->get(),'p_sql'=>$p_sql,'input'=>$input], 200);
    }
    public function getTournament($id){
        $tournament = Tournament::with(['tournamentImages', 'tournamentCategories'])->where('id', $id)->first();
        return response()->json(['data' => $tournament], 200);
    }
    // save tournament
    public function upload(Request $request){

        $data = $request->all();
        if($request->hasFile('files')){
            return response()->json(['abc' => $data], 200);
        }
    }
    // save tournament
    public function update_match_score(Request $request,$id){
        if(!empty($request->user_id) && $user = User::where('id', $request->user_id)->first()){
            if(!empty($request->tournament_id) && $tournament=Tournament::where('id', $request->tournament_id)->where('user_id', $request->user_id)->where('is_started',1)->get()->first()){
                if(!empty($request->matches)){
                    
                    
                    $matches=json_decode($request->matches);
                    foreach($matches as $match_row){
                        $tournament_row=Tournament::where('id', $request->tournament_id)->where('user_id', $request->user_id)->where('is_started',1)->get()->first();
                        $pending_match_teams=!empty($tournament_row->pending_match_teams) ? explode(",",$tournament_row->pending_match_teams) : array();
                        $winners_pool=!empty($tournament_row->winners_pool) ? explode(",",$tournament_row->winners_pool) : array();
                        if(!empty($match_row->id) &&  $match=TournamentMatches::where('id', $match_row->id)->get()->first()){
                            if(!empty($match_row->team1_score) && !empty($match_row->team2_score) && !empty($match_row->winner)){
                                if(in_array($match_row->team1,$pending_match_teams)){
                                    if($match_row->team1==$match_row->winner){
                                        $winner=$match_row->team1;
                                    }
                                    else{
                                        $winner=$match_row->team2;
                                        $key = array_search($match_row->team1, $pending_match_teams);
                                        $key1 = array_search($match_row->team1, $winners_pool);
                                        if ($key !== false) {
                                            unset($pending_match_teams[$key]);
                                        }
                                        if ($key1 !== false) {
                                            unset($winners_pool[$key1]);
                                        }
                                        $pending_match_teams[]=$winner;
                                        $winners_pool[]=$winner;
                                        if (!in_array($winner, $pending_match_teams)) {
                                            $pending_match_teams[] = $winner;
                                        }
                                        if (!in_array($winner, $winners_pool)) {
                                            $winners_pool[] = $winner;
                                        }
                                        
                                        $tournament_row->pending_match_teams=$pending_match_teams;
                                        $tournament_row->winners_pool=$winners_pool;
                                        $tournament_row->update();
                                    }
                                    
                                }
                                if(in_array($match_row->team2,$pending_match_teams)){
                                    if($match_row->team2==$match_row->winner){
                                        $winner=$match_row->team2;
                                    }
                                    else{
                                        $winner=$match_row->team1;
                                        $key = array_search($match_row->team2, $pending_match_teams);
                                        $key1 = array_search($match_row->team2, $winners_pool);
                                        if ($key !== false) {
                                            unset($pending_match_teams[$key]);
                                        }
                                        if ($key1 !== false) {
                                            unset($winners_pool[$key1]);
                                        }
                                        $pending_match_teams[]=$winner;
                                        $winners_pool[]=$winner;
                                        if (!in_array($winner, $pending_match_teams)) {
                                            $pending_match_teams[] = $winner;
                                        }
                                        if (!in_array($winner, $winners_pool)) {
                                            $winners_pool[] = $winner;
                                        }
                                        
                                        $tournament_row->pending_match_teams=$pending_match_teams;
                                        $tournament_row->winners_pool=$winners_pool;
                                        $tournament_row->update();
                                    }
                                    
                                }
                                $match->team1_score=$match_row->team1_score;
                                $match->team2_score=$match_row->team2_score;
                                $match->winner=$match_row->winner;
                                $match->looser=intval($match_row->winner)==$match->team1 ? $match->team2 : $match->team1;
                                $match->status=1;
                                $match->update();
                            }
                            else{
                                return response()->json(['msg' => 'Please fill in all fields before saving, and team 1 score and team 2 score must be greater than 0.!','status'=>0], 200);
                            }
                        }
                        else{
                            return response()->json(['msg' => 'Invalid match!','status'=>0], 200);
                        }
                    }
                    
                    return response()->json(['msg' => 'Score updated!','status'=>1], 200);
                }
                return response()->json(['msg' => 'No matches to proceed!','status'=>0], 200);
            }
            else{
                return response()->json(['msg' => 'Invalid Tournament!','status'=>0], 200);
            }
        }
        else{
            return response()->json(['msg' => 'Invalid User!','status'=>0], 200);
        }
    }
    public function save_match_score(Request $request,$id){
        if(!empty($request->user_id) && $user = User::where('id', $request->user_id)->first()){
            if(!empty($request->tournament_id) && $tournament=Tournament::where('id', $request->tournament_id)->where('user_id', $request->user_id)->where('is_started',1)->get()->first()){
                if(!empty($id) && $match=TournamentMatches::where('id', $id)->get()->first()){
                   $validator = Validator::make($request->all(), [
                        'team1_score' => 'required',
                        'team2_score' => 'required',
                        'winner' => 'required',
                    ]);
                     if ($validator->fails()) {
                        return response()->json(['msg' => $validator->errors(),'status'=>0], 200);
                    }
                    else{
                        if($tournament->match_type=='single'){
                            $match->team1_score=$request->team1_score;
                            $match->team2_score=$request->team2_score;
                            $match->winner=$request->winner;
                            $match->looser=intval($request->winner)==$match->team1 ? $match->team2 : $match->team1;
                            $match->status=1;
                            $match->update();
                            if(!empty($tournament->pending_match_teams) && $tournament->pending_match_teams!=null && $tournament->pending_match_teams!='null'){
                                $pending_teams_flag=$this->isCommaSeparated($tournament->pending_match_teams);

                                if($pending_teams_flag && $pending_teams_flag==true){
                                    $pending_teams_arr = explode(',', $tournament->pending_match_teams);
                                    array_push($pending_teams_arr, $request->winner);
                                    $tournament->winners_pool=implode(",",$pending_teams_arr);
                                    $tournament->pending_match_teams=implode(",",$pending_teams_arr);
                                    $tournament->update();
                                }
                                else{
                                    // print_r("..");die;
                                    $winners_arr=array();
                                    $winners_arr[]=$tournament->pending_match_teams;
                                    $winners_arr[]=$request->winner;
                                    $tournament->winners_pool=implode(",",$winners_arr);
                                    $tournament->pending_match_teams=implode(",",$winners_arr);
                                    $tournament->update();
                                }
                            }
                            else{
                                $tournament->pending_match_teams=$request->winner;
                                $tournament->winners_pool=$request->winner;
                                $tournament->update();
                            }
                            if(!empty($tournament->looser_pool) && $tournament->looser_pool!=null && $tournament->looser_pool!='null'){
                                $looser_pool_flag=$this->isCommaSeparated($tournament->looser_pool);
                                if($looser_pool_flag && $looser_pool_flag==true){
                                    $looser_pool_arr = explode(',', $tournament->looser_pool);
                                    $looser_item=intval($request->winner)==$match->team1 ? $match->team2 : $match->team1;
                                    array_push($looser_pool_arr, $looser_item);
                                    $tournament->looser_pool=implode(",",$looser_pool_arr);
                                    $tournament->update();
                                }
                                else{
                                    $looser_item=intval($request->winner)==$match->team1 ? $match->team2 : $match->team1;
                                    $loosers_arr=array();
                                    $loosers_arr[]=$tournament->looser_pool;
                                    $loosers_arr[]=$looser_item;
                                    $tournament->looser_pool=implode(",",$loosers_arr);
                                    $tournament->update();
                                }
                            }
                            else{
                                $looser_item=intval($request->winner)==$match->team1 ? $match->team2 : $match->team1;
                                $tournament->looser_pool=$looser_item;
                                $tournament->update();
                            } 
                        }
                        else if($tournament->match_type=='double'){
                            $match->team1_score=$request->team1_score;
                            $match->team2_score=$request->team2_score;
                            $match->winner=$request->winner;
                            $match->looser=intval($request->winner)==$match->team1 ? $match->team2 : $match->team1;
                            $match->status=1;
                            $match->update();
                            if(intval($request->final_round)==1){
                                $eleminated_pool_flag=$this->isCommaSeparated($tournament->eleminated_pool);
                                if($eleminated_pool_flag && $eleminated_pool_flag==true){
                                    $eleminated_pool_arr = explode(',', $tournament->eleminated_pool);
                                    $looser_item=intval($request->winner)==$match->team1 ? $match->team2 : $match->team1;
                                    array_push($eleminated_pool_arr, $looser_item);
                                    $tournament->eleminated_pool=implode(",",$eleminated_pool_arr);
                                    $tournament->update();
                                }
                            }
                            else{
                                if(intval($request->loose_round)!=1){
                                    //for winner side winners
                                    if(!empty($tournament->winners_pool) && $tournament->winners_pool!=null && $tournament->winners_pool!='null'){
                                        $winners_pool_flag=$this->isCommaSeparated($tournament->winners_pool);
                                        if($winners_pool_flag && $winners_pool_flag==true){
                                            $winners_pool_arr = explode(',', $tournament->winners_pool);
                                            array_push($winners_pool_arr, $request->winner);
                                            $tournament->winners_pool=implode(",",$winners_pool_arr);
                                            $tournament->available_teams=implode(",",$winners_pool_arr);
                                            $tournament->update();
                                        }
                                        else{
                                            $winners_arr=array();
                                            $winners_arr[]=$tournament->winners_pool;
                                            $winners_arr[]=$request->winner;
                                            $tournament->winners_pool=implode(",",$winners_arr);
                                            $tournament->available_teams=implode(",",$winners_arr);
                                            $tournament->update();
                                        }
                                    }
                                    else{
                                        $tournament->available_teams=$request->winner;
                                        $tournament->winners_pool=$request->winner;
                                        $tournament->update();
                                    }

                                    //for winner side loosers
                                    if(!empty($tournament->looser_pool) && $tournament->looser_pool!=null && $tournament->looser_pool!='null'){
                                        $looser_pool_flag=$this->isCommaSeparated($tournament->looser_pool);
                                        if($looser_pool_flag && $looser_pool_flag==true){
                                            $looser_pool_arr = explode(',', $tournament->looser_pool);
                                            $looser_item=intval($request->winner)==$match->team1 ? $match->team2 : $match->team1;
                                            array_push($looser_pool_arr, $looser_item);
                                            $tournament->looser_pool=implode(",",$looser_pool_arr);
                                            $tournament->update();
                                        }
                                        else{
                                            $looser_item=intval($request->winner)==$match->team1 ? $match->team2 : $match->team1;
                                            $loosers_arr=array();
                                            $loosers_arr[]=$tournament->looser_pool;
                                            $loosers_arr[]=$looser_item;
                                            $tournament->looser_pool=implode(",",$loosers_arr);
                                            $tournament->update();
                                        }
                                    }
                                    else{
                                        $looser_item=intval($request->winner)==$match->team1 ? $match->team2 : $match->team1;
                                        $tournament->looser_pool=$looser_item;
                                        $tournament->update();
                                    } 
                                }
                                else if(intval($request->loose_round)==1){
                                    //for loosers side loosers
                                    $eleminated_pool_flag=$this->isCommaSeparated($tournament->eleminated_pool);
                                    if($eleminated_pool_flag && $eleminated_pool_flag==true){
                                        $eleminated_pool_arr = explode(',', $tournament->eleminated_pool);
                                        $looser_item=intval($request->winner)==$match->team1 ? $match->team2 : $match->team1;
                                        array_push($eleminated_pool_arr, $looser_item);
                                        $tournament->eleminated_pool=implode(",",$eleminated_pool_arr);
                                        $tournament->update();
                                    }
                                    else{
                                        $eleminated_pool_arr=array();
                                        if(!empty($tournament->eleminated_pool)){
                                            $eleminated_pool_arr[]=$tournament->eleminated_pool;
                                        }
                                        $looser_item=intval($request->winner)==$match->team1 ? $match->team2 : $match->team1;
                                        
                                        $eleminated_pool_arr[]=$looser_item;
                                        $tournament->eleminated_pool=implode(",",$eleminated_pool_arr);
                                        $tournament->update();
                                    }
                                    //for loosers side winning
                                    if(!empty($tournament->looser_pool) && $tournament->looser_pool!=null && $tournament->looser_pool!='null'){
                                        $winners_pool_flag=$this->isCommaSeparated($tournament->looser_pool);
                                        if($winners_pool_flag && $winners_pool_flag==true){
                                            $winners_pool_arr = explode(',', $tournament->looser_pool);
                                            array_push($winners_pool_arr, $request->winner);
                                            $tournament->looser_pool=implode(",",$winners_pool_arr);
                                            $tournament->update();
                                        }
                                        else{
                                            $winners_arr=array();
                                            $winners_arr[]=$tournament->looser_pool;
                                            $winners_arr[]=$request->winner;
                                            $tournament->looser_pool=implode(",",$winners_arr);
                                            $tournament->update();
                                        }
                                    }
                                    else{
                                        $tournament->looser_pool=$request->winner;
                                        $tournament->update();
                                    }
                                }  
                            }
                        }
                        else{
                            return response()->json(['msg' => 'Invalid tournament bracket!','status'=>0], 200);
                        }
                        
                        
                        
                        $pendingMatches=TournamentMatches::where(['round_id'=>$match->round_id,'status'=>0])->count();
                        if($pendingMatches<=0){
                            TournamentRounds::where(['id'=>$match->round_id])->update(['status'=>'completed']);
                        }
                        return response()->json(['msg' => 'Score updated!','status'=>1], 200);
                    }
                }
                else{
                    return response()->json(['msg' => 'Invalid match!','status'=>0], 200);
                }
            }
            else{
                return response()->json(['msg' => 'Invalid tournament!','status'=>0], 200);
            }
        }
        else{
            return response()->json(['msg' => 'Invalid User!','status'=>0], 200);
        }
    }
    public function start_next_round(Request $request,$id){
        if(!empty($request->user_id) && $user = User::where('id', $request->user_id)->first()){
            if(!empty($id) && $tournament=Tournament::where('id', $id)->where('user_id', $request->user_id)->where('is_started',1)->get()->first()){
                $post = $request->all();
                if($tournament->match_type=='single'){
                        if($latestRound = TournamentRounds::where('tournament_id', $tournament->id)
                            ->where('status', 'in_progress')
                            ->orderBy('id', 'asc')
                            ->first()){
                            Tournament::where('id',$tournament->id)->update(array('is_bracket_generated'=>1));
                            // print_r($latestRound);die;
                            $latestRoundMatchIds=array();
                            if($latestCompletedRound = TournamentRounds::where('tournament_id', $tournament->id)
                            ->where('status', 'completed')
                            ->latest()->orderBy('id','desc')
                            ->first()){
                                $latestRoundMatchIds = TournamentMatches::where('round_id', $latestCompletedRound->id)
                                    ->pluck('winner', 'id')
                                    ->toArray();
                            }
                            // print_r($latestRoundMatchIds);die;
                            $total_round_matches=!empty($latestRound->matches) ? $latestRound->matches->count() : 0;
                            if($total_round_matches > 0){
                                $teamIds=[];
                                if(!empty($tournament->pending_match_teams)){
                                    if($this->isCommaSeparated($tournament->pending_match_teams)){
                                        $teamIds=explode(",",trim($tournament->pending_match_teams, ','));
                                    }
                                    else{
                                        $teamIds[]=$tournament->pending_match_teams;
                                    }  
                                }
                                // print_r($teamIds);die;
                                if(count($teamIds) > 1){
                                    if(!empty($latestRound->matches)){
                                        $chosen_matches=array();
                                        $chosenNumbers = [];
                                        $remainingTeams = $teamIds;
                                        foreach($latestRound->matches as $round_match_row){
                                            $result1 = $this->chooseTwoRandomNumbers($remainingTeams, $chosenNumbers);
                                            $chosenNumbers = array_merge($chosenNumbers, $result1['chosen']);
                                            $chosen_teams=$result1['chosen'];
                                            $remainingTeams = $result1['remaining'];
                                            $match_data=array('team1'=>$chosen_teams[0],'team2'=>$chosen_teams[1]);
                                            $chose0_find=array_search($chosen_teams[0], $latestRoundMatchIds);
                                            
                                            // print_r($round_match_row);die;
                                            
                                            $tournamentmatch_id=TournamentMatches::where('id',$round_match_row->id)->update($match_data);

                                            if($chose0_find !== false){
                                                if($matchRow=TournamentMatches::where('id', $chose0_find)->get()->first()){
                                                    $matchRow->next_match_id=$round_match_row->id;
                                                    $matchRow->update();
                                                }
                                                
                                            }
                                            $chose1_find=array_search($chosen_teams[1], $latestRoundMatchIds);
                                            if($chose1_find !== false){
                                                if($matchRow=TournamentMatches::where('id', $chose1_find)->get()->first()){
                                                    $matchRow->next_match_id=$round_match_row->id;
                                                    $matchRow->update();
                                                }
                                            }
                                        } 
                                        
                                        Tournament::where('id',$tournament->id)->update(array('pending_match_teams'=>implode(",", $remainingTeams)));
                                    }
                                }
                                
                            }

                        }
                        else{
                            return response()->json(['msg' => 'no tournament found!','status'=>0], 200);
                        }
                        return response()->json(['status'=>1,'tournament_id'=>$tournament->id], 200);
                }
                else if($tournament->match_type=='double'){
                    if($request->type=='win'){
                        $latestCompletedRound = TournamentRounds::where('tournament_id', $tournament->id)
                            ->where('status', 'completed')
                            ->latest()
                            ->first();
                         $teamIds=[];
                        if($this->isCommaSeparated($tournament->winners_pool)){
                            $teamIds=explode(",",$tournament->winners_pool);
                        }
                        else{
                            $teamIds[]=$tournament->winners_pool;
                        }
                        $total_number_matches=$this->findClosestNumber(count($teamIds));

                        $tournamentRound=TournamentRounds::create(array(
                            'tournament_id'=>$tournament->id,
                            'user_id'=>$user->id,
                            'type'=>$latestCompletedRound->type,
                            'round_no'=>intval($latestCompletedRound->round_no) + 1
                        ));
                        //total teams
                        $total_teams=$total_number_matches * 2;

                        $remaining_teams=array();
                        $chosenNumbers = [];
                        $remainingTeams = $teamIds;
                        $chosen_matches=array();
                        for($i=1;$i<=$total_number_matches;$i++){
                            $result1 = $this->chooseTwoRandomNumbers($remainingTeams, $chosenNumbers);
                            // print_r($result1);die;
                            $chosenNumbers = array_merge($chosenNumbers, $result1['chosen']);
                            $chosen_teams=$result1['chosen'];
                            $remainingTeams = $result1['remaining'];
                            TournamentMatches::create(array(
                                'round_id'=>$tournamentRound->id,
                                'team1'=>$chosen_teams[0],
                                'team2'=>$chosen_teams[1],
                            ));
                        }

                        $tournament->available_teams=count($remainingTeams) > 0 ? implode(",", $remainingTeams) : "";
                        $tournament->winners_pool=count($remainingTeams) > 0 ? implode(",", $remainingTeams) : "";
                        $tournament->is_started=1;
                        $tournament->match_type=$latestCompletedRound->type;
                        $tournament->update();
                        return response()->json(['chosen_matches' => $chosen_matches,'remainingTeams'=>$remainingTeams,'status'=>1,'tournament_id'=>$tournament->id,'round_id'=>$tournamentRound->id], 200);
                    }
                    else if($request->type=='lose'){
                        $latestCompletedRound = TournamentRounds::where('tournament_id', $tournament->id)
                            ->where('status', 'completed')
                            ->latest()
                            ->first();
                         $teamIds=[];
                         if($this->isCommaSeparated($tournament->looser_pool)){
                            $teamIds=explode(",",$tournament->looser_pool);
                        }
                        else{
                            $teamIds[]=$tournament->looser_pool;
                        }

                        $total_number_matches=$this->findClosestNumber(count($teamIds));




                         $tournamentRound=TournamentRounds::create(array(
                            'tournament_id'=>$tournament->id,
                            'user_id'=>$user->id,
                            'type'=>$latestCompletedRound->type,
                            'team_type'=>'lose',
                            'round_no'=>intval($latestCompletedRound->round_no) + 1
                        ));
                        //total teams
                        $total_teams=$total_number_matches * 2;

                        
                        $chosenNumbers = [];
                        $remainingTeams = $teamIds;
                        $chosen_matches=array();
                        for($i=1;$i<=$total_number_matches;$i++){
                            $result1 = $this->chooseTwoRandomNumbers($remainingTeams, $chosenNumbers);
                            // print_r($result1);die;
                            $chosenNumbers = array_merge($chosenNumbers, $result1['chosen']);
                            $chosen_teams=$result1['chosen'];
                            $remainingTeams = $result1['remaining'];
                            TournamentMatches::create(array(
                                'round_id'=>$tournamentRound->id,
                                'team1'=>$chosen_teams[0],
                                'team2'=>$chosen_teams[1],
                            ));
                        }

                        $tournament->available_teams=count($remainingTeams) > 0 ? implode(",", $remainingTeams) : "";
                        $tournament->looser_pool=count($remainingTeams) > 0 ? implode(",", $remainingTeams) : "";
                        $tournament->is_started=1;
                        $tournament->match_type=$latestCompletedRound->type;
                        $tournament->update();
                        return response()->json(['chosen_matches' => $chosen_matches,'remainingTeams'=>$remainingTeams,'status'=>1,'tournament_id'=>$tournament->id,'round_id'=>$tournamentRound->id], 200);
                    }
                    else if($request->type=='final'){
                        $latestCompletedRound = TournamentRounds::where('tournament_id', $tournament->id)
                            ->where('status', 'completed')
                            ->latest()
                            ->first();
                        $teamsCount = $tournament->teams->count();
                        if(!empty($tournament->eleminated_pool) && !empty($tournament->looser_pool) && !empty($tournament->winners_pool)){
                            $eleminated_pool_array=array();
                            $looser_pool_array=array();
                            $winners_pool_array=array();
                            if($this->isCommaSeparated($tournament->eleminated_pool)){
                                $eleminated_pool_array=explode(",",$tournament->eleminated_pool);
                            }
                            else{
                                $eleminated_pool_array[]=$tournament->eleminated_pool;
                            }
                            if($this->isCommaSeparated($tournament->looser_pool)){
                                $looser_pool_array=explode(",",$tournament->looser_pool);
                            }
                            else{
                                $looser_pool_array[]=$tournament->looser_pool;
                            }
                            if($this->isCommaSeparated($tournament->winners_pool)){
                                $winners_pool_array=explode(",",$tournament->winners_pool);
                            }
                            else{
                                $winners_pool_array[]=$tournament->winners_pool;
                            }
                            if(count($winners_pool_array)==1 && count($looser_pool_array)==1){
                                $tournamentRound=TournamentRounds::create(array(
                                    'tournament_id'=>$tournament->id,
                                    'user_id'=>$user->id,
                                    'type'=>'double',
                                    'team_type'=>'final',
                                    'round_no'=>intval($latestCompletedRound->round_no) + 1
                                ));
                                TournamentMatches::create(array(
                                    'round_id'=>$tournamentRound->id,
                                    'team1'=>$tournament->looser_pool,
                                    'team2'=>$tournament->winners_pool,
                                ));
                                $tournament->available_teams=null;
                                $tournament->winners_pool=null;
                                $tournament->looser_pool=null;
                                $tournament->is_started=1;
                                $tournament->update();
                                return response()->json(['status'=>1,'tournament_id'=>$tournament->id,'round_id'=>$tournamentRound->id], 200);
                            }
                            else{
                                return response()->json(['msg' => 'This is not final round!Technical problem here!','status'=>0], 200);
                            }
                            
                        }
                        else{
                            return response()->json(['msg' => 'This is not final round!Technical problem here!','status'=>0], 200);
                        }
                    }
                    else{
                        return response()->json(['msg' => 'Invalid double elemination request type!','status'=>0], 200);
                    }
                }
                else{
                    return response()->json(['msg' => 'Invalid request type!','status'=>0], 200);
                }


                    
            }
            else{
                return response()->json(['msg' => 'Invalid tournament!','status'=>0], 200);
            }
        }
        else{
            return response()->json(['msg' => 'Invalid User!','status'=>0], 200);
        }
    }
    public function generateTournamentMatches($tournament_id){
        if(!empty($tournament_id) && $tournament=Tournament::where('id', $tournament_id)->where('is_started',0)->get()->first()){
            $tournamentTeams = Team::where('tournament_id', $tournament->id)->where('status', 'accepted')->pluck('id')->toArray();
            if(count($tournamentTeams) > 0){
                $total_rounds=$this->calculateRounds(count($tournamentTeams));
                $remainingTeams='';
                // print_r($total_rounds);die;
                for($round_key=1;$round_key<=$total_rounds;$round_key++){
                    
                    
                    if($round_key==1){
                        $isRoundCreated=TournamentRounds::where(['tournament_id'=>$tournament->id,'round_no'=>$round_key])->get()->count();
                        if($isRoundCreated<=0){
                            $teamIds = Team::where('tournament_id', $tournament->id)->where('status', 'accepted')->pluck('id')->toArray();
                            $total_number_matches=$this->findClosestNumber(count($tournamentTeams));
                            $tournamentRound=TournamentRounds::create(array(
                                'tournament_id'=>$tournament->id,
                                'user_id'=>$tournament->user_id,
                                'type'=>'single',
                                'round_no'=>$round_key
                            ));
                            //total teams
                            $total_teams=$total_number_matches * 2;

                            $chosenNumbers = [];
                            $remainingTeams = $teamIds;
                            $chosen_matches=array();
                            for($i=1;$i<=$total_number_matches;$i++){
                                $result1 = $this->chooseTwoRandomNumbers($remainingTeams, $chosenNumbers);
                                    // print_r($result1);die;
                                $chosenNumbers = array_merge($chosenNumbers, $result1['chosen']);
                                $chosen_teams=$result1['chosen'];
                                // print_r($chosenNumbers);die;
                                $remainingTeams = $result1['remaining'];
                                $match_id=TournamentMatches::create(array(
                                    'round_id'=>$tournamentRound->id,
                                    'team1'=>$chosen_teams[0],
                                    'team2'=>$chosen_teams[1],
                                ));
                                
                            }

                            $tournament->available_teams=implode(",", $remainingTeams);
                            $tournament->pending_match_teams=implode(",", $remainingTeams);
                            $tournament->update(); 
                            
                        }

                        
                            
                    }
                    else{
                        $isRoundCreated=TournamentRounds::where(['tournament_id'=>$tournament->id,'round_no'=>$round_key])->get()->count();
                        
                        if($isRoundCreated<=0){


                            $tournament_available_teams_req=Tournament::where('id', $tournament_id)->where('is_started',0)->pluck('available_teams')->toArray();
                            $tournament_available_teams=$tournament_available_teams_req[0];
                            $teamIds=[];
                            if(!empty($tournament_available_teams)){
                                if($this->isCommaSeparated($tournament_available_teams)){
                                    $teamIds=explode(",",$tournament_available_teams);
                                }
                                else{
                                    $teamIds[]=$tournament_available_teams;
                                } 
                            }
                        
                        
                            if($latestRound = TournamentRounds::where('tournament_id', $tournament->id)
                                ->latest()
                                ->orderBy('id', 'desc')
                                ->first()){
                                $latestRoundMatches=0;
                                if($latestRound->matches){
                                    $latestRoundMatches=$latestRound->matches->count();
                                }
                                if($latestRoundMatches > 0){
                                    if($latestRoundMatches > 1){
                                        for($m_i=1;$m_i<=$latestRoundMatches;$m_i++){
                                            $teamIds[]=-1-$latestRound->round_no-$m_i;
                                        }
                                    }
                                }

                                if(count($teamIds) > 1){
                                    $total_number_matches=$this->findClosestNumber(count($teamIds));
                                    $tournamentRound=TournamentRounds::create(array(
                                        'tournament_id'=>$tournament->id,
                                        'user_id'=>$tournament->user_id,
                                        'type'=>$latestRound->type,
                                        'round_no'=>intval($latestRound->round_no) + 1
                                    ));
                                    $chosenNumbers = [];
                                    $remainingTeams = $teamIds;
                                    $chosen_matches=array();
                                    
                                    for($mi=1;$mi<=$total_number_matches;$mi++){
                                        $result1 = $this->chooseTwoRandomNumbers($remainingTeams, $chosenNumbers);
                                        
                                        $chosenNumbers = array_merge($chosenNumbers, $result1['chosen']);
                                        $remainingTeams = $result1['remaining'];

                                        

                                        TournamentMatches::create(array(
                                            'round_id'=>$tournamentRound->id,
                                            'team1'=>0,
                                            'team2'=>0,
                                            'next_match_id'=>0
                                        ));
                                        

                                    }
                                    
                                    $tournament->available_teams=!empty($remainingTeams) ? implode(",", $remainingTeams) : null;
                                    $tournament->update();

                                    // if($round_key!=$total_rounds){
                                        $currentCreatedMatchesIds = TournamentMatches::where('round_id', $tournamentRound->id)->pluck('id')->toArray();
                                        $previousRoundMatches = TournamentMatches::where('round_id', $latestRound->id)->get();

                                        foreach ($previousRoundMatches as $previousRoundMatch) {
                                            $randomStoredMatchId = $currentCreatedMatchesIds[array_rand($currentCreatedMatchesIds)];
                                            
                                            TournamentMatches::where(['id' => $previousRoundMatch->id])->update(['next_match_id' => $randomStoredMatchId]);
                                        }  
                                    // }
                                    

                                }
                            } 
                        }
                        
                    }
                }
                // print_r("yesss");die;
            }
            
        }
    }
    public function generateTournamentDoubleEliminationMatches($tournament_id){
        if(!empty($tournament_id) && $tournament=Tournament::where('id', $tournament_id)->where('is_started',0)->get()->first()){
            $tournamentTeams = Team::where('tournament_id', $tournament->id)->where('status', 'accepted')->pluck('id')->toArray();
            if(count($tournamentTeams) > 0){
                $total_rounds=$this->calculateDoubleEliminationRounds(count($tournamentTeams));
                $remainingTeams='';
                // print_r($total_rounds);die;
                for($round_key=1;$round_key<=$total_rounds;$round_key++){
                    
                    
                    if($round_key==1){
                        $isRoundCreated=TournamentRounds::where(['tournament_id'=>$tournament->id,'round_no'=>$round_key])->get()->count();
                        if($isRoundCreated<=0){
                            $teamIds = Team::where('tournament_id', $tournament->id)->where('status', 'accepted')->pluck('id')->toArray();
                            $total_number_matches=$this->findClosestNumber(count($tournamentTeams));
                            $tournamentRound=TournamentRounds::create(array(
                                'tournament_id'=>$tournament->id,
                                'user_id'=>$tournament->user_id,
                                'type'=>'double',
                                'round_no'=>$round_key
                            ));
                            //total teams
                            $total_teams=$total_number_matches * 2;

                            $chosenNumbers = [];
                            $remainingTeams = $teamIds;
                            $chosen_matches=array();
                            for($i=1;$i<=$total_number_matches;$i++){
                                $result1 = $this->chooseTwoRandomNumbers($remainingTeams, $chosenNumbers);
                                    // print_r($result1);die;
                                $chosenNumbers = array_merge($chosenNumbers, $result1['chosen']);
                                $chosen_teams=$result1['chosen'];
                                // print_r($chosenNumbers);die;
                                $remainingTeams = $result1['remaining'];
                                $match_id=TournamentMatches::create(array(
                                    'round_id'=>$tournamentRound->id,
                                    'team1'=>$chosen_teams[0],
                                    'team2'=>$chosen_teams[1],
                                ));
                                
                            }

                            $tournament->available_teams=implode(",", $remainingTeams);
                            $tournament->pending_match_teams=implode(",", $remainingTeams);
                            $tournament->update(); 
                            
                        }

                        
                            
                    }
                    else{
                        
                        $isRoundCreated=TournamentRounds::where(['tournament_id'=>$tournament->id,'round_no'=>$round_key])->get()->count();
                        print_r($isRoundCreated);die;
                        if($isRoundCreated<=0){


                            $tournament_available_teams_req=Tournament::where('id', $tournament_id)->where('is_started',0)->pluck('available_teams')->toArray();
                            $tournament_available_teams=$tournament_available_teams_req[0];
                            $teamIds=[];
                            if(!empty($tournament_available_teams)){
                                if($this->isCommaSeparated($tournament_available_teams)){
                                    $teamIds=explode(",",$tournament_available_teams);
                                }
                                else{
                                    $teamIds[]=$tournament_available_teams;
                                } 
                            }
                        
                        
                            if($latestRound = TournamentRounds::where('tournament_id', $tournament->id)
                                ->latest()
                                ->orderBy('id', 'desc')
                                ->first()){
                                $latestRoundMatches=0;
                                if($latestRound->matches){
                                    $latestRoundMatches=$latestRound->matches->count();
                                }
                                if($latestRoundMatches > 0){
                                    if($latestRoundMatches > 1){
                                        for($m_i=1;$m_i<=$latestRoundMatches;$m_i++){
                                            $teamIds[]=-1-$latestRound->round_no-$m_i;
                                        }
                                    }
                                }

                                if(count($teamIds) > 1){
                                    $total_number_matches=$this->findClosestNumber(count($teamIds));
                                    $tournamentRound=TournamentRounds::create(array(
                                        'tournament_id'=>$tournament->id,
                                        'user_id'=>$tournament->user_id,
                                        'type'=>$latestRound->type,
                                        'round_no'=>intval($latestRound->round_no) + 1
                                    ));
                                    $chosenNumbers = [];
                                    $remainingTeams = $teamIds;
                                    $chosen_matches=array();
                                    
                                    for($mi=1;$mi<=$total_number_matches;$mi++){
                                        $result1 = $this->chooseTwoRandomNumbers($remainingTeams, $chosenNumbers);
                                        
                                        $chosenNumbers = array_merge($chosenNumbers, $result1['chosen']);
                                        $remainingTeams = $result1['remaining'];

                                        

                                        TournamentMatches::create(array(
                                            'round_id'=>$tournamentRound->id,
                                            'team1'=>0,
                                            'team2'=>0,
                                            'next_match_id'=>0
                                        ));
                                        

                                    }
                                    
                                    $tournament->available_teams=!empty($remainingTeams) ? implode(",", $remainingTeams) : null;
                                    $tournament->update();

                                    // if($round_key!=$total_rounds){
                                        $currentCreatedMatchesIds = TournamentMatches::where('round_id', $tournamentRound->id)->pluck('id')->toArray();
                                        $previousRoundMatches = TournamentMatches::where('round_id', $latestRound->id)->get();

                                        foreach ($previousRoundMatches as $previousRoundMatch) {
                                            $randomStoredMatchId = $currentCreatedMatchesIds[array_rand($currentCreatedMatchesIds)];
                                            
                                            TournamentMatches::where(['id' => $previousRoundMatch->id])->update(['next_match_id' => $randomStoredMatchId]);
                                        }  
                                    // }
                                    

                                }
                            } 
                        }
                        
                    }
                }
                // print_r("yesss");die;
            }
            
        }
    }
     public function start_tournament(Request $request,$id){
        if(!empty($request->user_id) && $user = User::where('id', $request->user_id)->first()){
            if(!empty($id) && $tournament=Tournament::where('id', $id)->where('user_id', $request->user_id)->where('is_started',0)->get()->first()){
                $post = $request->all();
                if(!empty($request->type)){

                    //total tournament teams
                    $tournamant_teams_count=Team::where('status', 'accepted')->where('tournament_id',$tournament->id)->count();
                    if($tournamant_teams_count > 0){

                        $teamIds = Team::where('tournament_id', $tournament->id)->where('status', 'accepted')->pluck('id')->toArray();
                        $total_number_matches=$this->findClosestNumber($tournamant_teams_count);
                        if($request->type=='single'){
                            $this->generateTournamentMatches($tournament->id);
                            $tournament->is_started=1;
                            $tournament->match_type=$request->type;
                            $tournament->update();
                            
                            return response()->json(['status'=>1,'tournament_id'=>$tournament->id], 200);
                        }
                        else if($request->type=='double'){
                            $this->generateTournamentDoubleEliminationMatches($tournament->id);
                            $totalRounds=$this->calculateRounds($tournamant_teams_count);
                            print_r($totalRounds);die;
                             $tournamentRound=TournamentRounds::create(array(
                                'tournament_id'=>$tournament->id,
                                'user_id'=>$user->id,
                                'type'=>$request->type,
                                'round_no'=>1
                            ));
                            //total teams
                            $total_teams=$total_number_matches * 2;
    
                            $remaining_teams=array();
                            $chosenNumbers = [];
                            $remainingTeams = $teamIds;
                            $chosen_matches=array();
                            for($i=1;$i<=$total_number_matches;$i++){
                                $result1 = $this->chooseTwoRandomNumbers($remainingTeams, $chosenNumbers);
                                // print_r($result1);die;
                                $chosenNumbers = array_merge($chosenNumbers, $result1['chosen']);
                                $chosen_teams=$result1['chosen'];
                                $remainingTeams = $result1['remaining'];
                                TournamentMatches::create(array(
                                    'round_id'=>$tournamentRound->id,
                                    'team1'=>$chosen_teams[0],
                                    'team2'=>$chosen_teams[1],
                                ));
                            }
                            $tournament->available_teams=implode(",", $remainingTeams);
                            $tournament->winners_pool=implode(",", $remainingTeams);
                            $tournament->is_started=1;
                            $tournament->match_type=$request->type;
                            $tournament->update();
                            return response()->json(['chosen_matches' => $chosen_matches,'remainingTeams'=>$remainingTeams,'status'=>1,'tournament_id'=>$tournament->id,'round_id'=>$tournamentRound->id], 200);
                        }
                        else{
                             return response()->json(['msg' => 'Tournament type is invalid!','status'=>0], 200);
                        }
                        //success
                        
                    }
                    else{
                        return response()->json(['msg' => 'No teams are added to start the tournament!','status'=>0], 200);
                    }
                    
                    
                    
                }
                else{
                    return response()->json(['msg' => 'Type is required','status'=>0], 200);
                }
            }
            else{
                return response()->json(['msg' => 'Invalid tournament!','status'=>0], 200);
            }
        }
        else{
            return response()->json(['msg' => 'Invalid User!','status'=>0], 200);
        }
    }
    public function create(Request $request){
        
        // $data = $request->all();
        $data = $request->except(['staff_arr']);
        $staff_arr=$request->input("staff_arr",null);
        $matches=$request->input("matches",null);
        $staff_data=array();
        if(!empty($staff_arr)){
            $staff_data=json_decode($staff_arr);
        }
        if(!empty($matches)){
            $matches=json_decode($matches);
        }
        // print_r($matches);die;
        // if ($request->hasFile('tournament_logo')) {
        //         $tournament_logo=$data['tournament_logo'];
        //         $tournament_logo = $tournament_logo->store('uploads', 'public');
        //         $data['tournament_logo']=$tournament_logo;
        // }
        if ($request->hasFile('tournament_logo')) {
            $imageName = "logo_" . time() . '_' . uniqid() . '.' . $request->tournament_logo->extension();             
            $data['tournament_logo'] = $imageName;
        }
        $data['updated_at']=date('Y-m-d H:i:s');
        $data['lat']=json_decode($data['lat']);
        $data['long']=json_decode($data['long']);
        // print_r($data);die;
        $tournament = Tournament::create($data);
        if ($request->hasFile('logos')) {
            foreach($request->file('logos') as $logo){
                $logoName = "logo_" . time() . '_' . uniqid() . '.' . $logo->extension();
                $logo->move(public_path('uploads'), $logoName);
                $tournamentImage = new TournamentImage();
                $tournamentImage->tournament_id = $tournament->id;
                $tournamentImage->caption = 'logo';
                $tournamentImage->image = $logoName;
                $tournamentImage->save();
            }
        }
        if ($request->hasFile('documents')) {
            foreach($request->file('documents') as $document){
                $documentName = "document_" . time() . '_' . uniqid() . '.' . $document->extension();                    
                $document->move(public_path('uploads'), $documentName);                
                $tournamentImage = new TournamentImage();
                $tournamentImage->tournament_id = $tournament->id;
                $tournamentImage->caption = 'document';
                $tournamentImage->image = $documentName;
                $tournamentImage->save();
            }
        }
        if ($request->hasFile('banners')) {
            foreach($request->file('banners') as $banner){
                $bannerName = "banner_" . time() . '_' . uniqid() . '.' . $banner->extension();               
                $banner->move(public_path('uploads'), $bannerName);                
                $tournamentImage = new TournamentImage();
                $tournamentImage->tournament_id = $tournament->id;
                $tournamentImage->caption = 'banner';
                $tournamentImage->image = $bannerName;
                $tournamentImage->save();
            }
        }

        
        if($tournament){
            foreach($staff_data as $staff){
                $s_data=array(
                    'tournament_id'=>$tournament->id,
                    'contact'=>$staff->contact,
                    'responsibility'=>$staff->responsibility,
                );
                DB::table('tournament_staff')->insert($s_data);
            }
            foreach($matches as $match){
                $m_data=array(
                    'tournament_id'=>$tournament->id,
                    'schedule_date'=>date('Y-m-d',strtotime($match->schedule_date)),
                    'schedule_time'=>$match->schedule_time,
                    'schedule_breaks'=>$match->schedule_breaks,
                    'venue_availability'=>$match->venue_availability,
                );
                Tournament_matches_schedule::create($m_data);
            }
            return response()->json(
                [
                    'status'=>1,
                    'message' => 'Tournament saved successfully',
                    'tournament_id' => $tournament->id
                ], 
            200);
        }
        return response()->json(['message' => 'Something went wrong'], 400);
    }
    public function update_tournament(Request $request,$id){
        
        if(!empty($request->user_id) && $user = User::where('id', $request->user_id)->first()){
            if(!empty($id) && $tournament=Tournament::where('id', $id)->where('user_id', $request->user_id)->get()->first()){
                
                $data = $request->except(['staff_arr']);
                $staff_arr=$request->input("staff_arr",null);
                $matches=$request->input("matches",null);
                $banner_arr=$request->input("banner_arr",null);
                $documents_arr=$request->input("documents_arr",null);
                $logos_arr=$request->input("logos_arr",null);
                $staff_data=array();
                if(!empty($staff_arr)){
                    $staff_data=json_decode($staff_arr);
                }
                $matches_Arr=array();
                if(!empty($matches)){
                    $matches_Arr=json_decode($matches);
                }
                if(!empty($banner_arr)){
                    $banner_data=json_decode($banner_arr);
                }
                if(!empty($documents_arr)){
                    $documents_data=json_decode($documents_arr);
                }
                if(!empty($logos_arr)){
                    $logos_data=json_decode($logos_arr);
                }
                if ($request->hasFile('tournament_logo')) {
                    $imageName = "logo_" . time() . '_' . uniqid() . '.' . $request->tournament_logo->extension();          
                    $tournament_logo = $request->tournament_logo->move(public_path('uploads'), $imageName);            
                    $data['tournament_logo'] = $imageName;
                }
                $data['lat']=json_decode($data['lat']);
                $data['long']=json_decode($data['long']);
                // print_r($data);die;
                $tournament->update($data);
                // print_r($tournament);die;
                // if ($request->hasFile('logos')) {
                //     foreach($data['logos'] as $logo){
                //         $logo = $logo->store('uploads', 'public');
                //         $tournamentImage = new TournamentImage();
                //         $tournamentImage->tournament_id = $tournament->id;
                //         $tournamentImage->caption = 'logo';
                //         $tournamentImage->image = $logo;
                //         $tournamentImage->save();
                //     }
                // }
                //  if ($request->hasFile('documents')) {
                //     foreach($data['documents'] as $document){
                //         $document = $document->store('uploads', 'public');
                //         $tournamentImage = new TournamentImage();
                //         $tournamentImage->tournament_id = $tournament->id;
                //         $tournamentImage->caption = 'document';
                //         $tournamentImage->image = $document;
                //         $tournamentImage->save();
                //     }
                // }
                // if ($request->hasFile('banners')) {
                //     foreach($data['banners'] as $logo){
                //         $banner = $logo->store('uploads', 'public');
                //         $tournamentImage = new TournamentImage();
                //         $tournamentImage->tournament_id = $tournament->id;
                //         $tournamentImage->caption = 'banner';
                //         $tournamentImage->image = $banner;
                //         $tournamentImage->save();
                //     }
                // }
                if(!empty($logos_data)){
                    DB::table('tournament_images')->where('tournament_id', $tournament->id)->where('caption','logo')->delete();
                }
                if ($request->hasFile('logos')) {
                    foreach($request->file('logos') as $logo){
                        $logoName = "logo_" . time() . '_' . uniqid() . '.' . $logo->extension();
                        $logo->move(public_path('uploads'), $logoName);
                        $tournamentImage = new TournamentImage();
                        $tournamentImage->tournament_id = $tournament->id;
                        $tournamentImage->caption = 'logo';
                        $tournamentImage->image = $logoName;
                        $tournamentImage->save();
                    }
                }
                if(!empty($documents_data)){
                    DB::table('tournament_images')->where('tournament_id', $tournament->id)->where('caption','document')->delete();
                }
                if ($request->hasFile('documents')) {
                    foreach($request->file('documents') as $document){
                        $documentName = "document_" . time() . '_' . uniqid() . '.' . $document->extension();                    
                        $document->move(public_path('uploads'), $documentName);                
                        $tournamentImage = new TournamentImage();
                        $tournamentImage->tournament_id = $tournament->id;
                        $tournamentImage->caption = 'document';
                        $tournamentImage->image = $documentName;
                        $tournamentImage->save();
                    }
                }
                if(!empty($banner_data)){
                    DB::table('tournament_images')->where('tournament_id', $tournament->id)->where('caption','banner')->delete();
                }
                if ($request->hasFile('banners')) {
                    foreach($request->file('banners') as $banner){
                        $bannerName = "banner_" . time() . '_' . uniqid() . '.' . $banner->extension();               
                        $banner->move(public_path('uploads'), $bannerName);                
                        $tournamentImage = new TournamentImage();
                        $tournamentImage->tournament_id = $tournament->id;
                        $tournamentImage->caption = 'banner';
                        $tournamentImage->image = $bannerName;
                        $tournamentImage->save();
                    }
                }

                
                if($tournament){
                    if(!empty($staff_data)){
                        DB::table('tournament_staff')->where('tournament_id', $tournament->id)->delete();
                        foreach($staff_data as $staff){
                            $s_data=array(
                                'tournament_id'=>$tournament->id,
                                'contact'=>$staff->contact,
                                'responsibility'=>$staff->responsibility,
                            );
                            DB::table('tournament_staff')->insert($s_data);
                        }
                    }
                    // print_r($matches_Arr);die;
                    if(!empty($matches_Arr)){
                        Tournament_matches_schedule::where('tournament_id', $tournament->id)->delete();
                        foreach($matches_Arr as $match){
                            $m_data=array(
                                'tournament_id'=>$tournament->id,
                                'schedule_date'=>date('Y-m-d',strtotime($match->schedule_date)),
                                'schedule_time'=>$match->schedule_time,
                                'schedule_breaks'=>$match->schedule_breaks,
                                'venue_availability'=>$match->venue_availability,
                            );
                            Tournament_matches_schedule::create($m_data);
                        }
                    }
                    // pr($banner_data);
                    if(!empty($banner_data)){
                        // DB::table('tournament_images')->where('tournament_id', $tournament->id)->where('caption','banner')->delete();
                        foreach($banner_data as $banner){
                            $s_data=array(
                                'tournament_id'=>$tournament->id,
                                'caption'=>'banner',
                                'image'=>$banner->image,
                            );
                            DB::table('tournament_images')->insert($s_data);
                        }
                    }
                    if(!empty($logos_data)){
                        // DB::table('tournament_images')->where('tournament_id', $tournament->id)->where('caption','logo')->delete();
                        foreach($logos_data as $logo){
                            $s_data=array(
                                'tournament_id'=>$tournament->id,
                                'caption'=>'logo',
                                'image'=>$logo->image,
                            );
                            DB::table('tournament_images')->insert($s_data);
                        }
                    }
                    if(!empty($documents_data)){
                        // DB::table('tournament_images')->where('tournament_id', $tournament->id)->where('caption','document')->delete();
                        foreach($documents_data as $document){
                            $s_data=array(
                                'tournament_id'=>$tournament->id,
                                'caption'=>'document',
                                'image'=>$document->image,
                            );
                            DB::table('tournament_images')->insert($s_data);
                        }
                    }
                    return response()->json(
                        [
                            'message' => 'Tournament updated successfully',
                            'status' => 1,
                            'tournament_id' => $tournament->id
                        ], 
                    200);
                }
            }
            else{
                return response()->json(['message' => 'Invalid tournament request!'], 200);
            }
        }
        return response()->json(['message' => 'invalid user!'], 200);
    }
    // update tournament
    public function update(Request $request, $id){
        $data = $request->all();
        $tournament = Tournament::where('id', $id)->update($data);
        // save tournament images
        if($request->hasFile('images')){
            foreach($request->file('images') as $image){
                $name = time().'_'.$image->getClientOriginalName();
                $image->move(public_path('uploads/tournament'), $name);
                $tournamentImage = new TournamentImage();
                $tournamentImage->tournament_id = $id;
                $tournamentImage->image = $name;
                $tournamentImage->save();
            }
        }
        // save tournament categories
        if($request->has('categories')){
            foreach($request->categories as $category){
                $tournamentCategory = new TournamentCategory();
                $tournamentCategory->tournament_id = $id;
                $tournamentCategory->category_id = $category;
                $tournamentCategory->save();
            }
        }
        return response()->json(['message' => 'Tournament updated successfully'], 200);
    }
    // delete tournament
    public function delete($id){
        $tournament = Tournament::where('id', $id)->delete();
        return response()->json(['message' => 'Tournament deleted successfully'], 200);
    }

    public function updatePaymentStatus($id){
        $tournament = Tournament::where('id', $id)->update(['payment_status' => 1]);
        return response()->json(['message' => 'Payment status updated successfully'], 200);
    }
    // create-indent-payment
    public function create_stripe_intent(Request $request){
        $res=array();
        $res['status']=0;
        // $header = $request->header('Authorization');
        // $member=$this->authenticate_verify_token($header);
        $input = $request->all();
        
        if($input){
            if(!empty($request->user_id) && $user = User::where('id', $request->user_id)->first()){
                $stripe = new StripeClient('sk_test_51Moz1CFV8hMVqQzQZoplqqUTXaaIbqrJanKVG7hpwvHsH3x7uUl4euomLaicugVmjmXlga2ftQHvQ4UJNUHcDnNk00wom1iTYm');
                try{
                    $amount = $input['amount'];
                    if(!empty($input['expires_in'])){
                        // $expires_in=$input['expires_in'];
                        // $total=floatval($amount) * intval($expires_in);
                        $total=floatval($amount);
                    }
                    else{
                        $total=floatval($amount);
                    }
                    
                    $cents = intval($total * 500);
                    // if(!empty($member->customer_id)){
                    //     $customer_id=$member->customer_id;
                    // }
                    // else{
                        $customer = $stripe->customers->create([
                            'email' =>$user->email,
                            'name' =>!empty($request->card_holder) ?  $request->card_holder : $user->name,
                            // 'address' => $stripe_adddress,
                        ]);
                        $customer_id=$customer->id;
                    // }

                    $intent= $stripe->paymentIntents->create([
                        'amount' => $cents,
                        'currency' => 'usd',
                        'customer'=>$customer_id,
                        // 'payment_method' => $vals['payment_method'],
                        'setup_future_usage' => 'off_session',
                    ]);
                    $setupintent=$stripe->setupIntents->create([
                        'customer' => $customer_id,
                    ]);
                    // return response()->json(['data' => $setupintent], 200);
                    $arr=array(
                            'paymentIntentId'=>$intent->id,
                            'setup_client_secret'=>$setupintent->client_secret,
                            'setup_intent_id'=>$setupintent->id,
                            'client_secret'=>$intent->client_secret,
                            'customer'=>$customer_id,
                            'status'=>1
                    );
                    $res['arr']=$arr;
                    $res['status']=1;
                    return response()->json(['data' => $res], 200);
                        // print_r($res);
                }
                catch(Exception $e) {
                    $arr['msg']="Error >> ".$e->getMessage();
                    $arr['status']=0;
                }
            }
            else{
                $arr['msg']="Error >> Invalid user.Please login again to continue!";
                $arr['status']=0;
            }
        }
        exit(json_encode($res));
    }
    
}