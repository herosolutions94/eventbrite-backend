<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Components\Services\OrganizationService;
use App\Models\Tournament;
use App\Models\TournamentType;
use App\Models\EventType;
use App\Models\TournamentLevel;
use App\Models\TournamentRounds;
use App\Models\TournamentMatches;
use App\Models\Team;
use App\Http\Requests\StoreTournamentRequest;
use Illuminate\Support\Facades\DB;


class TournamentController extends Controller
{
    public function index()
    {
        $tournament = Tournament::with('category')->latest()->paginate(10);
        return view('admin.tournaments.index',
            [
                'data' => $tournament,
            ]);
    }
    public function show($id)
    {
        $tournament = Tournament::with(
            'images', 
            'tournamentCategories',
            'category',
            'teams.teamMembers',
            'reviews.user',
            'tournamentType',
            )->findOrFail($id);
        // return $tournament;
        if($tournament){
            $tournament->tournamenType=TournamentType::where('id',$tournament->type)->get()->first();
            $tournament->EventType=EventType::where('id',$tournament->event_type)->get()->first();
            $tournament->TournamentLevel=TournamentLevel::where('id',$tournament->level)->get()->first();
            $tournament->documents_arr=$tournament->documents;
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

            $tournament->logos_arr=$tournament->logos;
            $tournament->banner_arr=$tournament->banners;
            if($tournament->firstRound){
                $tournament->firstRound=$tournament->firstRound;
                $tournament->firstRound->matches=$tournament->firstRound->matches;
                foreach($tournament->firstRound->matches as $m_key=>$match){
                    $match->team_1=$match->team_1;
                    $match->team_2=$match->team_2;
                    $match->winner_row=$match->winner_row;
                }
            }
            // dd($tournament->firstRound);
        }
        return view('admin.tournaments.show', compact('tournament'));
    }
    public function create()
    {
        return view('admin.tournaments.create');
    }

    public function store(StoreTournamentRequest $request)
    {
        $data = $request->validated();
        $tournament = Tournament::create($data);
        if($tournament){
            return redirect()->route('admin.tournaments.index')->with('success', 'Tournament saved successfully');
        }
        return redirect()->route('admin.tournaments.index')->with('error', 'Something went wrong');
    }
    public function edit($id)
    {
        $tournament = Tournament::findOrFail($id);
        return view('admin.tournaments.edit', compact('tournament'));
    }
    public function update(StoreTournamentRequest $request, $id)
    {
        $tournament = Tournament::findOrFail($id);
        $data = $request->validated();
        $tournament->update($data);
        return redirect()->route('admin.tournaments.index')->with('success', 'Tournament updated successfully');
    }
    public function featured(Request $request)
    {
        $tournament = Tournament::findOrFail($request->id);
        $tournament->is_featured = $request->is_featured;
        $tournament->save();
        return redirect()->route('admin.tournaments.index')->with('success', 'Tournament updated successfully');
    }
    public function destroy($id)
    {
        $tournament = Tournament::findOrFail($id);
        $tournament->is_active = 0;
        $tournament->save();
        return redirect()->route('admin.tournaments.index')->with('success', 'Tournament deleted successfully');
    }

    public function activate($id)
    {
        $tournament = Tournament::findOrFail($id);
        $tournament->is_active = 1;
        $tournament->save();
        return redirect()->route('admin.tournaments.index')->with('success', 'Tournament activated successfully');
    }
    public function reset_tournament(Request $request,$id){
        if(!empty($id) && $tournament=Tournament::where('id', $id)->get()->first()){
            // $rounds=TournamentRounds::where(['tournament_id'=>$tournament->id])->get();
            // foreach($rounds as $round){
            //     TournamentMatches::where('round_id', $round->id)->delete();
            // }
            // TournamentRounds::where(['tournament_id'=>$tournament->id])->delete();
            $tournamant_teams_count=Team::where('status', 'accepted')->where('tournament_id',$tournament->id)->count();
            if($tournamant_teams_count > 0){
                $total_number_matches=$this->findClosestNumber($tournamant_teams_count);
                if($tournament->match_type=='single'){
                    $this->generateTournamentMatches($tournament->id);
                    return redirect()->route('admin.tournaments.show', ['id' => 50])->with('success', 'Tournament reset successfully');
                }
            }
        }
        else{
            return redirect()->route('admin.tournaments.index')->with('error', 'Invalid Tournament!');
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

    
}