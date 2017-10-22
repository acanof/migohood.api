<?php

	namespace App\Http\Controllers;

	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\Crypt;
	use Illuminate\Contracts\Encryption\DecryptException;
	use	Illuminate\Encryption\Encrypter;
	use validator;
	use Aws\S3\S3Client;
	use Aws\Exception\AwsException;
	use Aws\S3\Exception\S3Exception;
	use DateTime;
	use DB;
	use App\Models\Service_Type_Category;
	use App\Models\Service_Category;
	use App\Models\Type_Category;
	use App\Models\Guest;
	use App\Models\Service;
	use App\Models\User;
	use App\Models\Category;
	use App\Models\Languaje;
	use App\Models\Service_Languaje;
	use App\Models\Check_in;
	use App\Models\Price_history_has_duration;
	use App\Models\Duration;
	use App\Models\Payment;
	use App\Models\Service_Payment;
	use App\Models\Price_History;
	use App\Models\Amenite;
	use App\Models\State;
	use App\Models\Check_out;
	use App\Models\City;
	use App\Models\Country;
	use App\Models\Service_Rules;
	use App\Models\Service_Reservation;
	use App\Models\Service_Description;
	use App\Models\SpecialDate;
	use App\Models\Service_Amenite;
	use App\Models\Service_Type;
	use App\Models\Image;
	use App\Models\Type;
	use App\Models\Service_Calendar;
	use App\Models\Service_Accommodation;
	use App\Models\Calendar;
	use App\Models\Availability;
	use App\Models\Emergency_Number;
	use App\Models\Service_Emergency;
	use App\Models\Image_Duration;
	use App\Models\Bedroom;

	class WorkspaceController extends Controller {
		//Muestra todo los service
	    public function ReadService(){
	    //Se obtiene todos los servicios que se crean
	    return Service::all();
    	}

    	public function AddNewWorkspaceStep(Request $request){
	         $rule=[
	            'user_id' => 'required|numeric|min:1',
	            'category_code'=>'required|numeric|min:1',
	        ];
	        $validator=Validator::make($request->all(),$rule);
	        if ($validator->fails()) {
	            return response()->json($validator->errors()->all());
	        }else{
              $user=User::select()->where('id',$request->input("user_id"))->first();
              $code=2;
              $category=Category::select('id')->where('code',$code)->get();
	              if(count($user)>0){
	                 if(count($category)>0){
	                     $newspace=new Service;
	                     $dt = new DateTime();
	                     $newspace->date=$dt->format('Y-m-d (H:m:s)');
	                     $newspace->user_id=$user->id;
	                     $newspace->save();
	                     foreach ($category as $categorys){
	                            $newservicateg=new Service_Category;
	                            $newservicateg->service_id=$newspace->id;
	                            $newservicateg->category_id=$categorys->id;
	                            $newservicateg->save();
	                            
	                           }
	                     return  response()->json($newspace);
	                }else{
	                     return  response()->json("Category Not Found");
	                 }
	              }else{
	                 return  response()->json("User Not Found");
	              }
	        }

	    }

	    public function ReturnStep(Request $request){
		           $rule=[
		           'service_id' => 'required|numeric|min:1'
		      ];
		      $validator=Validator::make($request->all(),$rule);
		      if ($validator->fails()) {
		            return response()->json($validator->errors()->all());
		      }else{
		             $getreturn = DB::table('service')->join('service_category','service.id','=','service_category.service_id')
		              ->join('service_category','service.id','=','service_category.service_id')
		              ->join('category','category.id','=','service_category.category_id')
		              ->where('service_id','=',$request->input("service_id"))
		              ->where('service_category.category_id','=','2')
		              ->where('category.languaje','=',$request->input("languaje"))
		              ->select('service.id','service.date','category.name')
		             ->get();
		             if(count($getreturn)>0){
		                    return response()->json($getreturn);
		             }else{
		                     return response()->json('Return Not Found');
		             }

		     }
		}

		public function TypeGet(Request $request){
	          $rule=[
	                'languaje'=>'required'
	          ];
	          $validator=Validator::make($request->all(),$rule);
	          if ($validator->fails()) {
	                return response()->json($validator->errors()->all());
	          }else{
	                $type=Type::select('id','name','code')->where('category_id','=',2)->where('languaje','=',$request->input("languaje"))->get();
	                if(count($type)>0){
	                      return response()->json($type);
	                }else{
	                      return response()->json("Type not found");
	                }
	          }
	    }

	//Agregar Place type(workspace-step1)-Web
	    public function AddNewWorkspaceStep1(Request $request){
	        $rule=[
	            // Comente esto, ya que aun no poseo ningun id service
	            'service_id' => 'required|numeric|min:1',
	            'type_code'=>'required|numeric|min:1',
	            'live'=>'required|boolean'
	        ];
	        $validator=Validator::make($request->all(),$rule);
	        if ($validator->fails()) {
	            return response()->json($validator->errors()->all());
	        } else {
	            $service=Service::select()->where('id',$request->input("service_id"))->first();
	            $type=Type::select('id')->where('category_id','=',2)->where('code','=',$request->input("type_code"))->get();
	                if(count($service)>0){
	                    if(count($type)>0){
	                          $valtype=Service_Type::select()->where('service_id',$service->id)->first();
	                          try{
	                              if(count($valtype)==0){
	                                DB::table('service')->where('id',$service->id)->update(
	                                ['live'=>$request->input("live"),
	                                'num_space'=>$request->input("num_space"),
	                                ]);

	                                foreach($type as $types){
	                                  $newtype=new Service_Type;
	                                  $newtype->service_id = $service->id;
	                                  $newtype->type_id=$types->id;
	                                  $newtype->save();
	                                 }
	                                return response()->json('Add Type ');	                              	
	                              }else{
		                            DB::table('service_type')->where('service_id',$service->id)->delete();
		                            DB::table('service')->where('id',$service->id)->update(
		                                [
		                                'live'=>$request->input("live"),
		                                'num_space'=>$request->input("num_space"),
		                                ]);

		                                foreach($type as $types){
		                                  $newtype=new Service_Type;
		                                  $newtype->service_id = $service->id;
		                                  $newtype->type_id=$types->id;
		                                  $newtype->save();
		                                 }
		                                  return response()->json('Update Type ');
		                            }
	                          	
	                          }catch(exception $e){
	                              return response()->json($e);
	                          }
	                    	
	                    }else{
	                       return response()->json('Type not found');
	                    }
	                	
	                }else{
	                   return response()->json('Category not found');
	                }	
	        	}

	        }
	    

		public function ReturnStep1(Request $request){
		            $rule=[
		           'service_id' => 'required|numeric'
		      ];
		      $validator=Validator::make($request->all(),$rule);
		      if ($validator->fails()) {
		            return response()->json($validator->errors()->all());
		      }else{
		               $getstep1 = DB::table('service')
		              ->join('service_type','service_type.service_id','=','service.id')
		              ->join('type','type.id','=','service_type.type_id')
		              ->where('service.id','=',$request->input("service_id"))
		              ->where('type.languaje','=',$request->input("languaje"))
		              ->select('service.id','service.live','type.name as Type', 'service.num_space')
		              ->first() ;
		              if(count($getstep1)>0){
		                    return response()->json($getstep1);
		              }else{
		                    return response()->json('Not Found');
		              }
		      }

		    }

//Agregar Service(space-step2)-Web
    public function AddNewWorkspaceStep2(Request $request){
        $rule=[
           'service_id' => 'required|numeric|min:1',
           'num_guest'=>'required|numeric|min:0',
           'num_bedroom'=>'required|numeric'
        ];
        $validator=Validator::make($request->all(),$rule);
        if ($validator->fails()) {
            return response()->json($validator->errors()->all());
        } else {
            $servicespace=Service::select()->where('id',$request->input("service_id"))->first();
            if(count($servicespace)>0){

                $servicespace->num_guest=$request->input("num_guest");
                //$servicespace->save();
                DB::table('service')->where('id',$servicespace->id)->update(
                            ['num_guest'=> $request->input("num_guest"),
                            ]);
                $val=Bedroom::where('service_id',$servicespace->id)->first();
                if(count($val)==0){
                    for($i=1;$i<=$request->input("num_bedroom");$i++){
                        $bedroom=new Bedroom;
                        $bedroom->service_id=$servicespace->id;
                        $bedroom->save();
                    }
                }else{
                    DB::table('bedroom')->where('service_id',$servicespace->id)->delete();
                    for($i=1;$i<=$request->input("num_bedroom");$i++){
                        $bedroom=new Bedroom;
                        $bedroom->service_id=$servicespace->id;
                        $bedroom->save();
                    }
                }
                return response()->json("Add Space Bedroom");

            } else {
                return response()->json('Service not found');
            }
        }

   }

   //Agregar Service(space-step2-beds)-Web
   public function AddNewWorkspaceStep2Beds(Request $request){
        $rule=[
           'service_id' => 'required|numeric|min:1',
           'bedroom_id'=>'required|numeric|min:0',
           'double_bed'=>'numeric|min:0',
           'queen_bed'=>'numeric|min:0',
           'individual_bed'=>'numeric|min:0',
           'sofa_bed'=>'numeric|min:0',
           'other_bed'=>'numeric|min:0'

        ];
        $validator=Validator::make($request->all(),$rule);
        if ($validator->fails()) {
            return response()->json($validator->errors()->all());
        }else{
            $servicespace=Service::select()->where('id',$request->input("service_id"))->first();
            if(count($servicespace)>0){
              $bedroom=Bedroom::select()->where('service_id','=',$servicespace->id)->where('id','=',$request->input("bedroom_id"))->first();
              if(count($bedroom)>0){
                $val=Bedroom_Bed::select()->where('bedroom_id','=',$bedroom->id)->first();
                if(count($val)==0){
                try{
                 $newbedroomdouble=new Bedroom_Bed;
                 $newbedroomdouble->bedroom_id=$bedroom->id;
                 $newbedroomdouble->bed_id=1;
                 $newbedroomdouble->quantity=$request->input("double_bed");
                 $newbedroomdouble->save();
                 $newbedroomdouble=new Bedroom_Bed;
                 $newbedroomdouble->bedroom_id=$bedroom->id;
                 $newbedroomdouble->bed_id=6;
                 $newbedroomdouble->quantity=$request->input("double_bed");
                 $newbedroomdouble->save();
                 $newbedroomqueen=new Bedroom_Bed;
                 $newbedroomqueen->bedroom_id=$bedroom->id;
                 $newbedroomqueen->bed_id=2;
                 $newbedroomqueen->quantity=$request->input("queen_bed");
                 $newbedroomqueen->save();
                 $newbedroomqueen=new Bedroom_Bed;
                 $newbedroomqueen->bedroom_id=$bedroom->id;
                 $newbedroomqueen->bed_id=7;
                 $newbedroomqueen->quantity=$request->input("queen_bed");
                 $newbedroomqueen->save();
                 $newbedroomindividual=new Bedroom_Bed;
                 $newbedroomindividual->bedroom_id=$bedroom->id;
                 $newbedroomindividual->bed_id=3;
                 $newbedroomindividual->quantity=$request->input("individual_bed");
                 $newbedroomindividual->save();
                 $newbedroomindividual=new Bedroom_Bed;
                 $newbedroomindividual->bedroom_id=$bedroom->id;
                 $newbedroomindividual->bed_id=8;
                 $newbedroomindividual->quantity=$request->input("individual_bed");
                 $newbedroomindividual->save();
                 $newbedroomsofa=new Bedroom_Bed;
                 $newbedroomsofa->bedroom_id=$bedroom->id;
                 $newbedroomsofa->bed_id=4;
                 $newbedroomsofa->quantity=$request->input("sofa_bed");
                 $newbedroomsofa->save();
                 $newbedroomsofa=new Bedroom_Bed;
                 $newbedroomsofa->bedroom_id=$bedroom->id;
                 $newbedroomsofa->bed_id=9;
                 $newbedroomsofa->quantity=$request->input("sofa_bed");
                 $newbedroomsofa->save();
                 $newbedroomother=new Bedroom_Bed;
                 $newbedroomother->bedroom_id=$bedroom->id;
                 $newbedroomother->bed_id=5;
                 $newbedroomother->quantity=$request->input("other_bed");
                 $newbedroomother->save();
                 $newbedroomother=new Bedroom_Bed;
                 $newbedroomother->bedroom_id=$bedroom->id;
                 $newbedroomother->bed_id=10;
                 $newbedroomother->quantity=$request->input("other_bed");
                 $newbedroomother->save();
                    return response()->json('Add Bedroom-Beds');
                }catch(Exception $e){
                    return response()->json($e);
                }
            }else{
                     DB::table('bedroom_bed')->where('bedroom_id',$bedroom->id)->where('bed_id',1)->update(
                            ['quantity'=>$request->input("double_bed"),
                            ]);
                     DB::table('bedroom_bed')->where('bedroom_id',$bedroom->id)->where('bed_id',6)->update(
                            ['quantity'=>$request->input("double_bed"),
                            ]);
                     DB::table('bedroom_bed')->where('bedroom_id',$bedroom->id)->where('bed_id',2)->update(
                            ['quantity'=>$request->input("queen_bed"),
                            ]);
                     DB::table('bedroom_bed')->where('bedroom_id',$bedroom->id)->where('bed_id',7)->update(
                            ['quantity'=>$request->input("queen_bed"),
                            ]);
                     DB::table('bedroom_bed')->where('bedroom_id',$bedroom->id)->where('bed_id',3)->update(
                            ['quantity'=>$request->input("individual_bed"),
                            ]);
                     DB::table('bedroom_bed')->where('bedroom_id',$bedroom->id)->where('bed_id',8)->update(
                            ['quantity'=>$request->input("individual_bed"),
                            ]);
                     DB::table('bedroom_bed')->where('bedroom_id',$bedroom->id)->where('bed_id',4)->update(
                            ['quantity'=>$request->input("sofa_bed"),
                            ]);
                     DB::table('bedroom_bed')->where('bedroom_id',$bedroom->id)->where('bed_id',9)->update(
                            ['quantity'=>$request->input("sofa_bed"),
                            ]);
                     DB::table('bedroom_bed')->where('bedroom_id',$bedroom->id)->where('bed_id',5)->update(
                            ['quantity'=>$request->input("other_bed"),
                            ]);
                     DB::table('bedroom_bed')->where('bedroom_id',$bedroom->id)->where('bed_id',10)->update(
                            ['quantity'=>$request->input("other_bed"),
                            ]);

                     return response()->json('Update  Bedroom-Beds');
                 }
              }else{
                    return response()->json('Bedroom not found');
              }
            }else{
                   return response()->json('Service not found');
            }
        }
     }

    public function ReturnStep2(Request $request){
             $rule=[
           'service_id' => 'required|numeric'
      ];
      $validator=Validator::make($request->all(),$rule);
      if ($validator->fails()) {
            return response()->json($validator->errors()->all());
      }else{
            $getstep2 = DB::table('service')->join('bedroom','service.id','=','bedroom.service_id')
            ->where('service.id','=',$request->input("service_id"))
            ->select('service.id','service.num_guest', DB::raw('count(*) as num_bedroom')/*'bedroom.id as num_bedroom'*/)
            ->orderBy('bedroom.id','desc')
            //->take(1)
            ->get();
            if(count($getstep2)>0){
                  return response()->json($getstep2);
            }else{
                  return response()->json('Not Found');
            }
      }
    }


	public function ReturnStep2Beds(Request $request){
            $rule=[
          'user_id'=>'required|min:1',
          'bedroom_id'=>'required|min:1',
          'languaje' => 'required'
      ];
      $validator=Validator::make($request->all(),$rule);
      if ($validator->fails()) {
        return response()->json($validator->errors()->all());
      }else{
        $exist=DB::table('service')
                  ->join('bedroom','bedroom.service_id','=','service.id')
                  ->where('service.user_id','=',$request->input("user_id"))
                  ->where('bedroom.id','=',$request->input("bedroom_id"))
                  ->select('bedroom.id')
                  ->get();
        if(count($exist)>0){
          $newbedbedroomdata=DB::table('service')
                              ->join('bedroom','bedroom.service_id','=','service.id')
                              ->leftjoin('bedroom_bed','bedroom_bed.bedroom_id','=','bedroom.id')
                              ->leftjoin('bed','bed.id','=','bedroom_bed.bed_id')
                              ->where('service.user_id','=',$request->input("user_id"))
                              ->where('bedroom.id','=',$request->input("bedroom_id"))
                              ->where('bed.languaje','=',$request->input("languaje"))
                              ->select('bedroom_bed.*', 'bed.type as type')
                              ->get();
          //if(count($newbedbedroomdata)>0){
          return response()->json($newbedbedroomdata);
          /*}else{
            return response()->json("The user does not have a room or user not found");

          }*/
        }else{
          return response()->json("The user does not have a room or user not found");

        }

      }

    }

    public function AddNewStep3(Request $request){
         $rule=[ // 'service_id'=>'required|numeric|min:1',
              //'num_bathroom'=>'required|numeric|min:1'
            ];
            $validator=Validator::make($request->all(),$rule);
            if ($validator->fails()) {
                return response()->json($validator->errors()->all());
            }else{
                $service=Service::where('id',$request->input("service_id"))->first();
                if(count($service)>0){
                    $service->num_bathroom=$request->input("num_bathroom");
                    $service->save();
                    return response()->json('Add Step 3');
                }else{
                    return response()->json('Service not found');
                }
            }
    }


    public function ReturnStep3(Request $request){
           $rule=[
           'service_id' => 'required|numeric'
      ];
      $validator=Validator::make($request->all(),$rule);
      if ($validator->fails()) {
            return response()->json($validator->errors()->all());
      }else{
        $getstep3=Service::select('id','num_bathroom')->where('id','=',$request->input("service_id"))->first();
        if(count($getstep3)>0){
             return response()->json($getstep3);
        }else{
             return response()->json('Not Found');
        }
      }

    }

		public function AddNewWorkspaceStep4(Request $request){
		            $rule=[
		           'service_id' => 'required|numeric|min:1',
		          // 'country_id'=>'numeric|min:1',
		           'city_id'=>'numeric|min:1',
		        //   'state_id'=>'numeric|min:1',

		      ];
		      $validator=Validator::make($request->all(),$rule);
		      if ($validator->fails()) {
		        return response()->json($validator->errors()->all());
		        }else{
		            $servicespace=Service::select()->where('id',$request->input("service_id"))->first();
		        //    $country=Country::select()->where('id',$request->input("country_id"))->first();
		          //  $state=State::select()->where('country_id',$country->id)->where('id',$request->input("state_id"))->first();
		            $city=City::select()->where('id',$request->input("city_id"))->first();
		            if(count($servicespace)>0){
		                if(count($city)>0){
		                   // if(count($state)>0){
		                     //   if(count($country)>0){
		                            $val=Service_Description::select()->where('service_id',$request->input("service_id"))->first();
		                            if(count($val)==0){
		                                  DB::table('service')->where('id',$servicespace->id)->update(
		                                    ['city_id'=>$request->input("city_id"),
		                               ]);
		                                 DB::table('service')->where('id',$servicespace->id)->update(
		                                    ['zipcode'=>$request->input("zipcode"),
		                               ]);
		                                $des_address1=new Service_Description;
		                                $des_address1->service_id=$servicespace->id;
		                                $des_address1->description_id=2;
		                                $des_address1->content=$request->input("address1");
		                                $des_address1->save();
		                                $des_apt=new Service_Description;
		                                $des_apt->service_id=$servicespace->id;
		                                $des_apt->description_id=3;
		                                $des_apt->content=$request->input("apt");
		                                $des_apt->save();
		                                $des_neighborhood=new Service_Description;
		                                $des_neighborhood->service_id=$servicespace->id;
		                                $des_neighborhood->description_id=4;
		                                $des_neighborhood->content=$request->input("des_neighborhood");
		                                $des_neighborhood->save();
		                                $des_around=new Service_Description;
		                                $des_around->service_id=$servicespace->id;
		                                $des_around->description_id=5;
		                                $des_around->content=$request->input("des_around");
		                                $des_around->save();
		                                $des_longitude=new Service_Description;
		                                $des_longitude->service_id=$servicespace->id;
		                                $des_longitude->description_id=6;
		                                $des_longitude->content=$request->input("des_longitude");
		                                $des_longitude->save();
		                                $des_latitude=new Service_Description;
		                                $des_latitude->service_id=$servicespace->id;
		                                $des_latitude->description_id=7;
		                                $des_latitude->content=$request->input("des_latitude");
		                                $des_latitude->save();
		                                return response()->json('Add Location');
		                            }else{
		                              $serv_desloc = Service_Description::all();
		                                  foreach ($serv_desloc as $sdl) {
		                                    if($sdl->description_id >= 2 ){
		                                      if($sdl->description_id <= 7){
		                                        DB::table('service_description')->where('service_id',$servicespace->id)->where('description_id',$sdl->description_id)->delete();
		                                      }
		                                    }
		                                  }
		                                  // DB::table('service_description')->where('service_id',$servicespace->id)->delete();
		                                     DB::table('service')->where('id',$servicespace->id)->update(
		                                    ['city_id'=>$request->input("city_id"),
		                               ]);
		                                 DB::table('service')->where('id',$servicespace->id)->update(
		                                    ['zipcode'=>$request->input("zipcode"),
		                               ]);
		                                $des_address1=new Service_Description;
		                                $des_address1->service_id=$servicespace->id;
		                                $des_address1->description_id=2;
		                                $des_address1->content=$request->input("address1");
		                                $des_address1->save();
		                                $des_apt=new Service_Description;
		                                $des_apt->service_id=$servicespace->id;
		                                $des_apt->description_id=3;
		                                $des_apt->content=$request->input("apt");
		                                $des_apt->save();
		                                $des_neighborhood=new Service_Description;
		                                $des_neighborhood->service_id=$servicespace->id;
		                                $des_neighborhood->description_id=4;
		                                $des_neighborhood->content=$request->input("des_neighborhood");
		                                $des_neighborhood->save();
		                                $des_around=new Service_Description;
		                                $des_around->service_id=$servicespace->id;
		                                $des_around->description_id=5;
		                                $des_around->content=$request->input("des_around");
		                                $des_around->save();
		                                $des_longitude=new Service_Description;
		                                $des_longitude->service_id=$servicespace->id;
		                                $des_longitude->description_id=6;
		                                $des_longitude->content=$request->input("des_longitude");
		                                $des_longitude->save();
		                                $des_latitude=new Service_Description;
		                                $des_latitude->service_id=$servicespace->id;
		                                $des_latitude->description_id=7;
		                                $des_latitude->content=$request->input("des_latitude");
		                                $des_latitude->save();
		                                return response()->json('Update Location');
		                            }
		                   /*     }else{
		                           return response()->json('Country not found');
		                        }

		                    }else{
		                         return response()->json('State not found');
		                    } */
		                }else{
		                     return response()->json('City not found');
		                }

		            }else{
		                return response()->json('Service not found');
		            }

		        }

		     }


		    public function ReturnStep4(Request $request){
		           $rule=[
		           'service_id' => 'required|numeric'
		      ];
		      $validator=Validator::make($request->all(),$rule);
		      if ($validator->fails()) {
		            return response()->json($validator->errors()->all());
		      }else{
		            $getstep4 = DB::table('service')
		             ->join('service_description','service_description.service_id','=','service.id')
		            ->join('description','service_description.description_id','=','description.id')
		            ->join('city','service.city_id','=','city.id')
		            ->join('state','city.state_id','=','state.id')
		            ->join('country','country.id','=','state.country_id')
		            ->where('service.id','=',$request->input("service_id"))
		            ->select('service.id','service.zipcode','city.name as city','state.name as state','state.id as state_id','country.name as country','country.id as country_id','description.type','service_description.content')
		            ->get();
		            if(count($getstep5)>0){
		                 return response()->json($getstep4);
		            }else{

		             return response()->json('Not Found');
		            }
		      }
		    }

 public function AddNewStep5(Request $request){
        $rule=[
            'amenitie_code'=>'required|numeric|min:1',
            'service_id'=>'required|numeric|min:1'
        ];
        $validator=Validator::make($request->all(),$rule);
        if ($validator->fails()) {
            return response()->json($validator->errors()->all());
        }else{
            // Selecciono los amenites que posean el código recibido
            $amenites=Amenite::select('id')->where('category_id','=',2)->where('code',$request->input("amenitie_code"))->get();
            if(count($amenites)>0){
                // Selecciono el service que posea el id recibido
                $service=Service::select()->where('id',$request->input("service_id"))->first();
                if(count($service)==0){
                  
                    // Recorro el array amenities e inserto cada uno en la tabla interseccion Service_Amenite junto con el service correspondiente
                      foreach ($amenites as $amenite){

                       $newserviceame=new Service_Amenite;
                       $newserviceame->service_id=$service->id;
                       $newserviceame->amenite_id=$amenite->id;
                       $newserviceame->check=$request->input('check');
                       $newserviceame->save();
                      }

                    return response()->json('Add Step 5');
                }
                else{
                /*  DB::table('service_amenites')
                  ->where('service_id',$service->id)
                //  ->where('service_amenites.amenite_id','=',$amenites->id)
                  ->delete();*/

                  foreach ($amenites as $amenite){

                       $newserviceame=new Service_Amenite;
                       $newserviceame->service_id=$service->id;
                       $newserviceame->amenite_id=$amenite->id;
                       $newserviceame->check=$request->input('check');
                       $newserviceame->save();
                      }
                    return response()->json('Update Step 5');
                }
            }
            else{
                return response()->json('Amenite not found');
            }
        }
    }

    public function GetWorkspaceAmenities(Request $request){
	      $rule=[
	           'languaje' => 'required'
	      ];
	      $validator=Validator::make($request->all(),$rule);
	      if ($validator->fails()) {
	            return response()->json($validator->errors()->all());
	      }else{
	            $amenitie = DB::table('amenities')
	           // ->join('service_amenites','service_amenites.amenite_id','=','amenities.id')
	            ->where('languaje','=',$request->input("languaje"))
	            ->where('category_id','=',2)
	            ->select('code','name','type_amenities_id')
	            ->get();
	            if(count($amenitie)>0){
	                  return response()->json($amenitie);
	            }else{
	                  return response()->json("Amenities not found");
	            }
	      }
	    }

    public function ReturnStep5(Request $request){
              $rule=[
           'service_id' => 'required|numeric'
      ];
    $validator=Validator::make($request->all(),$rule);
      if ($validator->fails()) {
            return response()->json($validator->errors()->all());
      }else{
        $getstep5=DB::table('service')
        ->join('service_amenites','service_amenites.service_id','=','service.id')
        ->join('amenities','amenities.id','=','service_amenites.amenite_id')
        ->where('service.id','=',$request->input("service_id"))
        ->where('amenities.languaje','=',$request->input("languaje"))
	     ->where('amenities.category_id','=',2)
        ->select('service.id','amenities.name', 'amenities.type_amenities_id', 'amenities.code','service_amenites.check')
        ->get();
        if(count($getstep5)>0){
                return response()->json($getstep5);
        }else{
                return response()->json("Not Found");
        }
      }
    }


	    public function AddNewWorkspaceStep6(Request $request){
	    	//
	    }


		public function ReturnStep6(Request $request){
			    $rule=[
		           'service_id' => 'required|numeric'
		      ];
		    $validator=Validator::make($request->all(),$rule);
		      if ($validator->fails()) {
		            return response()->json($validator->errors()->all());
		      }else{
		         $getstep6=DB::table('service')
		        ->join('service_payment','service_payment.service_id','=','service.id')
		        ->join('payment','payment.code','=','service_payment.payment_id')
		        ->join('price_history','price_history.service_id','=','service.id')
		        ->join('price_history_has_duration','price_history_has_duration.price_history_service_id','=','price_history.service_id')
		        ->join('duration','duration.id','=','price_history_has_duration.duration_id')
		        ->join('check_in','check_in.service_id','=','service.id')
		        ->join('check_out','check_out.service_id','=','service.id')
		        ->join('currency','currency.id','=','price_history.currency_id')
		        ->where('service.id','=',$request->input("service_id"))
		        ->where('payment.languaje','=',$request->input("languaje"))
		        ->where('duration.languaje','=',$request->input("languaje"))
		        ->select('payment.type as Type-Payment','duration.type as Type-Duration'
		        ,'price_history.price as Price','currency.currency_iso as Currency-Name'
		        ,'check_in.time_entry as Time-Entry','check_in.until as Until'
		        ,'check_out.departure_time as Departure-Time')
		        ->get();
		         if(count($getstep6)>0){
		                return response()->json($getstep6);
		        }else{
		                return response()->json("Not Found");
		        }
		      }
	    }

   public function AddNewWorkspaceStep7(Request $request){
          $rule=[
           'service_id' => 'required|numeric|min:1',
           'bool_socialize'=>'boolean',
           'bool_available'=>'boolean',
           'des_title' => 'required',
           'description' =>'required',

           ];
      $validator=Validator::make($request->all(),$rule);
      if ($validator->fails()) {
        return response()->json($validator->errors()->all());
        }else{
            $service=Service::where('id',$request->input("service_id"))->first();
            if(count($service)>0){
                $valdescription=Service_Description::where('service_id',$service->id)->get();
                if(count($valdescription)==0){
                try{
                  $des_title=new Service_Description;
                  $des_title->service_id=$service->id;
                  $des_title->description_id=1;
                  $des_title->content=$request->input("des_title");
                  $des_title->save();
                  $des_description=new Service_Description;
                  $des_description->service_id=$service->id;
                  $des_description->description_id=8;
                  $des_description->content=$request->input("description");
                  $des_description->save();
                  $des_crib=new Service_Description;
                  $des_crib->service_id=$service->id;
                  $des_crib->description_id=9;
                  $des_crib->content=$request->input("desc_crib");
                  $des_crib->save();
                  $des_acc=new Service_Description;
                  $des_acc->service_id=$service->id;
                  $des_acc->description_id=10;
                  $des_acc->content=$request->input("desc_acc");
                  $des_acc->save();
                  $socialize=new Service_Description;
                  $socialize->service_id=$service->id;
                  $socialize->description_id=11;
                  $socialize->check=$request->input("bool_socialize");
                  $socialize->save();
                  $available=new Service_Description;
                  $available->service_id=$service->id;
                  $available->description_id=12;
                  $available->check=$request->input("bool_available");
                  $available->save();
                  $des_guest=new Service_Description;
                  $des_guest->service_id=$service->id;
                  $des_guest->description_id=13;
                  $des_guest->content=$request->input("desc_guest");
                  $des_guest->save();
                  $des_note=new Service_Description;
                  $des_note->service_id=$service->id;
                  $des_note->description_id=14;
                  $des_note->content=$request->input("desc_note");
                  $des_note->save();
                  return response()->json('Add Step-7');
                }catch(Exception $e){
                    return response()->json($e);
               }
            }else{
              $serv_des= Service_Description::get();
              DB::table('service_description')->where('service_id',$service->id)->where('description_id','1')->delete();
              foreach ($serv_des as $sd) {
                if($sd->description_id >= 8){
                  DB::table('service_description')->where('service_id',$service->id)->where('description_id',$sd->description_id)->delete();
                }
              }
              
                  $des_title=new Service_Description;
                  $des_title->service_id=$service->id;
                  $des_title->description_id=1;
                  $des_title->content=$request->input("des_title");
                  $des_title->save();
                  $des_description=new Service_Description;
                  $des_description->service_id=$service->id;
                  $des_description->description_id=8;
                  $des_description->content=$request->input("description");
                  $des_description->save();
                  $des_crib=new Service_Description;
                  $des_crib->service_id=$service->id;
                  $des_crib->description_id=9;
                  $des_crib->content=$request->input("desc_crib");
                  $des_crib->save();
                  $des_acc=new Service_Description;
                  $des_acc->service_id=$service->id;
                  $des_acc->description_id=10;
                  $des_acc->content=$request->input("desc_acc");
                  $des_acc->save();
                  $socialize=new Service_Description;
                  $socialize->service_id=$service->id;
                  $socialize->description_id=11;
                  $socialize->check=$request->input("bool_socialize");
                  $socialize->save();
                  $available=new Service_Description;
                  $available->service_id=$service->id;
                  $available->description_id=12;
                  $available->check=$request->input("bool_available");
                  $available->save();
                  $des_guest=new Service_Description;
                  $des_guest->service_id=$service->id;
                  $des_guest->description_id=13;
                  $des_guest->content=$request->input("desc_guest");
                  $des_guest->save();
                  $des_note=new Service_Description;
                  $des_note->service_id=$service->id;
                  $des_note->description_id=14;
                  $des_note->content=$request->input("desc_note");
                  $des_note->save();
                  return response()->json('Update Step-7');
                }
            }else{
                return response()->json('Service not found');
            }
        }
   }

	    public function ReturnStep7(Request $request){
	           $rule=[
	           'service_id' => 'required|numeric'
	      ];
	    $validator=Validator::make($request->all(),$rule);
	      if ($validator->fails()) {
	            return response()->json($validator->errors()->all());
	      }else{
	        $getstep7=DB::table('service')
	        ->join('service_description','service_description.service_id','=','service.id')
	        ->join('description','description.id','=','service_description.description_id')
	        ->where('service_id','=',$request->input("service_id"))
	        ->select('service_description.content','service_description.check','service_description.description_id')
	        ->get();
	          if(count($getstep7)>0){
	                return response()->json($getstep7);
	        }else{
	                return response()->json("Not Found");
	        }

	      }
	    }


   public function AddNewWorkspaceStep8(Request $request){
          $rule=[
           'service_id' => 'required|numeric|min:1',
           'AptoDe2a12'=>'boolean',
           'AptoDe0a2'=>'boolean',
           'SeadmitenMascotas'=>'boolean',
           'PermitidoFumar'=>'boolean',
           'Eventos'=>'boolean',
           'guest_phone'=>'boolean',
           'guest_email'=>'boolean',
           'guest_profile'=>'boolean',
           'guest_payment'=>'boolean',
           'guest_provided'=>'boolean',
           'guest_recomendation'=>'boolean'
          ];
      $validator=Validator::make($request->all(),$rule);
      if ($validator->fails()) {
        return response()->json($validator->errors()->all());
        }else{
            $service=Service::where('id',$request->input("service_id"))->first();
            if(count($service)>0){
               $valtrules=Service_Rules::where('service_id',$service->id)->get();
               if(count($valtrules)==0){
                try{
                 $newrules=new Service_Rules;
                 $newrules->service_id=$service->id;
                 $newrules->rules_id=1;
                 $newrules->check=$request->input("AptoDe2a12");
                 $newrules->save();
                 $newrules=new Service_Rules;
                 $newrules->service_id=$service->id;
                 $newrules->rules_id=2;
                 $newrules->check=$request->input("AptoDe0a2");
                 $newrules->save();
                 $newrules=new Service_Rules;
                 $newrules->service_id=$service->id;
                 $newrules->rules_id=3;
                 $newrules->check=$request->input("SeadmitenMascotas");
                 $newrules->save();
                 $newrules=new Service_Rules;
                 $newrules->service_id=$service->id;
                 $newrules->rules_id=4;
                 $newrules->check=$request->input("PermitidoFumar");
                 $newrules->save();
                 $newrules=new Service_Rules;
                 $newrules->service_id=$service->id;
                 $newrules->rules_id=5;
                 $newrules->check=$request->input("Eventos");
                 $newrules->save();
                 $newrules=new Service_Rules;
                 $newrules->service_id=$service->id;
                 $newrules->rules_id=6;
                 $newrules->description=$request->input("Desc_Otro_Evento");
                 $newrules->save();
                 $newrequirement=new Service_Rules;
                 $newrequirement->service_id=$service->id;
                 $newrequirement->rules_id=7;
                 $newrequirement->check=$request->input("guest_phone");
                 $newrequirement->save();
                 $newrequirement=new Service_Rules;
                 $newrequirement->service_id=$service->id;
                 $newrequirement->rules_id=8;
                 $newrequirement->check=$request->input("guest_email");
                 $newrequirement->save();
                 $newrequirement=new Service_Rules;
                 $newrequirement->service_id=$service->id;
                 $newrequirement->rules_id=9;
                 $newrequirement->check=$request->input("guest_profile");
                 $newrequirement->save();
                 $newrequirement=new Service_Rules;
                 $newrequirement->service_id=$service->id;
                 $newrequirement->rules_id=10;
                 $newrequirement->check=$request->input("guest_payment");
                 $newrequirement->save();
                 $newrequirement=new Service_Rules;
                 $newrequirement->service_id=$service->id;
                 $newrequirement->rules_id=11;
                 $newrequirement->check=$request->input("guest_provided");
                 $newrequirement->save();
                 $newrequirement=new Service_Rules;
                 $newrequirement->service_id=$service->id;
                 $newrequirement->rules_id=12;
                 $newrequirement->check=$request->input("guest_recomendation");
                 $newrequirement->save();
                 $newrules=new Service_Rules;
                 $newrules->service_id=$service->id;
                 $newrules->rules_id=13;
                 $newrules->description=$request->input("Desc_Instructions");
                 $newrules->save();
                 $newrules=new Service_Rules;
                 $newrules->service_id=$service->id;
                 $newrules->rules_id=14;
                 $newrules->description=$request->input("Desc_Name_Network");
                 $newrules->save();
                 $newrules=new Service_Rules;
                 $newrules->service_id=$service->id;
                 $newrules->rules_id=15;
                 if($request->input("Password_Wifi") != ''){
                 $newrules->description=Crypt::encrypt($request->input("Password_Wifi"));
                 }else{
                  $newrules->description=$request->input("Password_Wifi");
                 }
                 $newrules->save();
                 return response()->json('Add Step-8');
                }catch(Exception $e){
                       return response()->json($e);
                }
            }else{
                DB::table('service_rules')->where('service_id',$service->id)->delete();
                  $newrules=new Service_Rules;
                 $newrules->service_id=$service->id;
                 $newrules->rules_id=1;
                 $newrules->check=$request->input("AptoDe2a12");
                 $newrules->save();
                 $newrules=new Service_Rules;
                 $newrules->service_id=$service->id;
                 $newrules->rules_id=2;
                 $newrules->check=$request->input("AptoDe0a2");
                 $newrules->save();
                 $newrules=new Service_Rules;
                 $newrules->service_id=$service->id;
                 $newrules->rules_id=3;
                 $newrules->check=$request->input("SeadmitenMascotas");
                 $newrules->save();
                 $newrules=new Service_Rules;
                 $newrules->service_id=$service->id;
                 $newrules->rules_id=4;
                 $newrules->check=$request->input("PermitidoFumar");
                 $newrules->save();
                 $newrules=new Service_Rules;
                 $newrules->service_id=$service->id;
                 $newrules->rules_id=5;
                 $newrules->check=$request->input("Eventos");
                 $newrules->save();
                 $newrules=new Service_Rules;
                 $newrules->service_id=$service->id;
                 $newrules->rules_id=6;
                 $newrules->description=$request->input("Desc_Otro_Evento");
                 $newrules->save();
                 $newrequirement=new Service_Rules;
                 $newrequirement->service_id=$service->id;
                 $newrequirement->rules_id=7;
                 $newrequirement->check=$request->input("guest_phone");
                 $newrequirement->save();
                 $newrequirement=new Service_Rules;
                 $newrequirement->service_id=$service->id;
                 $newrequirement->rules_id=8;
                 $newrequirement->check=$request->input("guest_email");
                 $newrequirement->save();
                 $newrequirement=new Service_Rules;
                 $newrequirement->service_id=$service->id;
                 $newrequirement->rules_id=9;
                 $newrequirement->check=$request->input("guest_profile");
                 $newrequirement->save();
                 $newrequirement=new Service_Rules;
                 $newrequirement->service_id=$service->id;
                 $newrequirement->rules_id=10;
                 $newrequirement->check=$request->input("guest_payment");
                 $newrequirement->save();
                 $newrequirement=new Service_Rules;
                 $newrequirement->service_id=$service->id;
                 $newrequirement->rules_id=11;
                 $newrequirement->check=$request->input("guest_provided");
                 $newrequirement->save();
                 $newrequirement=new Service_Rules;
                 $newrequirement->service_id=$service->id;
                 $newrequirement->rules_id=12;
                 $newrequirement->check=$request->input("guest_recomendation");
                 $newrequirement->save();
                 $newrules=new Service_Rules;
                 $newrules->service_id=$service->id;
                 $newrules->rules_id=13;
                 $newrules->description=$request->input("Desc_Instructions");
                 $newrules->save();
                 $newrules=new Service_Rules;
                 $newrules->service_id=$service->id;
                 $newrules->rules_id=14;
                 $newrules->description=$request->input("Desc_Name_Network");
                 $newrules->save();
                 $newrules=new Service_Rules;
                 $newrules->service_id=$service->id;
                 $newrules->rules_id=15;
                 if($request->input("Password_Wifi") != ''){
                 $newrules->description=Crypt::encrypt($request->input("Password_Wifi"));
                 }else{
                  $newrules->description=$request->input("Password_Wifi");
                 }
                 $newrules->save();
                 return response()->json('Update Step-8');

                }
            }else{
                 return response()->json('Service not found');
            }
       }
   }

	
    public function ReturnStep8Rules(Request $request){
            $rule=[
           'service_id' => 'required|numeric'
      ];
    $validator=Validator::make($request->all(),$rule);
      if ($validator->fails()) {
            return response()->json($validator->errors()->all());
      }else{
        $getstep8=DB::table('service')
        ->join('service_rules','service_rules.service_id','=','service.id')
        ->join('house_rules','house_rules.id','=','service_rules.rules_id')
        ->where('service.id','=',$request->input("service_id"))
        ->select('service_rules.description as Description','service_rules.check as Check','service_rules.rules_id')
        ->get();
        if(count($getstep8)>0){
                return response()->json($getstep8);
        }else{
                return response()->json("Not Found");
        }
      }
    }

   public function AddNewWorkspaceStep9(Request $request){
        $rule=[
           'service_id' => 'required|numeric|min:1',
           'image'=>'required|image'
           ];
      $validator=Validator::make($request->all(),$rule);
      if ($validator->fails()) {
        return response()->json($validator->errors()->all());
        }else{
            $service=Service::where('id',$request->input("service_id"))->first();
            if(count($service)>0){
                 try{
                    // Se definen las credenciales del cliente s3 de amazon
                    $s3 = new S3Client([
                        'version'     => env('S3_VERSION'),
                        'region'      => env('S3_REGION'),
                        'credentials' => [
                            'key'    => env('S3_KEY'),
                            'secret' => env('S3_SECRET')
                        ]
                    ]);
                    $image_link = 'https://s3.'.env('S3_REGION').'.amazonaws.com/'.env('S3_BUCKET').'/files/images/';
                    // Obtenemos el campo file definido en el formulario
                    $file = $request->file('image');
                    // Creamos un nombre para nuestro imagen
                    $name = 'image'.str_random(20).'_service_'.$service->id.'.'.$file->getClientOriginalExtension();
                    // Movemos el archivo a la caperta temporal
                    $file->move('/files/images/',$name);
                    $newruta=new Image();
                    $old_image = str_replace($image_link,'',$newruta->ruta);
                    $s3->putObject([
                    'Bucket' => env('S3_BUCKET'),
                    'Key'    => '/files/images/'.$name,
                    'Body'   => fopen('/files/images/'.$name,'r'),
                    'ACL'    => 'public-read'
                    ]);
                     unlink('/files/images/'.$name);
                    $newruta->service_id=$service->id;
                    $newruta->ruta=$image_link.$name;
                    $newruta->description=$request->input("description");
                    $newruta->save();
                    // Borramos el arrchivo de la carpeta temporal

                    // Actualizamos la fila thumbnail del usuario respectivo
                    /*DB::table('imagen')->where('service_id', $service->id )->update(['ruta' => $image_link.$name,
                    'description'=>$request->input("description")]);*/

                    return json_encode('completed!', true);
                    return response()->json('Update completed!', true);
                }
                catch (\Exception $e){
                    return response()->json($e->getMessage());
                }
            }else{
              return response()->json('Service not found');
            }

        }
    }


    public function ReturnStep9(Request $request)
    {

       $rule=[
          'service_id' => 'required|numeric',
          //'image_id'=>'required'
        ];
      $validator=Validator::make($request->all(),$rule);
      if ($validator->fails()) {
            return response()->json($validator->errors()->all());
      }else{
          $image=DB::table('image')->where('service_id',$request->input("service_id"))/*->where('id',$request->input("image_id"))*/->get();
            if(count($image)>0){
                  return response()->json($image);
          }else{
                return response()->json("Not Found");
          }
      }
    }


    public function AddNewWorkspaceStep10(Request $request)
    {$rule=[
           'service_id' => 'required|numeric|min:1',
           'image'=>'required|image',
           'duration_code'=>'required|numeric|min:1',
           'price'=>'required|numeric|min:1',
           'currency_id'=>'required|numeric|min:1'
           ];
      $validator=Validator::make($request->all(),$rule);
      if ($validator->fails()) {
        return response()->json($validator->errors()->all());
        }else{
            $service=Service::where('id',$request->input("service_id"))->first();
            if(count($service)>0){
            // Se definen las credenciales del cliente s3 de amazon
                    $s3 = new S3Client([
                        'version'     => env('S3_VERSION'),
                        'region'      => env('S3_REGION'),
                        'credentials' => [
                            'key'    => env('S3_KEY'),
                            'secret' => env('S3_SECRET')
                        ]
                    ]);
                    $image_link = 'https://s3.'.env('S3_REGION').'.amazonaws.com/'.env('S3_BUCKET').'/files/service_images/';
                    // Obtenemos el campo file definido en el formulario
                    $file = $request->file('image');
                    // Creamos un nombre para nuestro imagen
                    $name = 'image'.str_random(20).'_service-image_'.$service->id.'.'.$file->getClientOriginalExtension();
                    // Movemos el archivo a la caperta temporal
                    $file->move('/files/service_images/',$name);
                    $newruta=new Image();
                    $old_image = str_replace($image_link,'',$newruta->ruta);
                    $s3->putObject([
                    'Bucket' => env('S3_BUCKET'),
                    'Key'    => '/files/service_images/'.$name,
                    'Body'   => fopen('/files/service_images/'.$name,'r'),
                    'ACL'    => 'public-read'
                    ]);
                     unlink('/files/service_images/'.$name);
                    $newruta->service_id=$service->id;
                    $newruta->ruta=$image_link.$name;
                    $newruta->description=$request->input("description");
                    $newruta->save();
                    $duration=Duration::select('id')->where('code',$request->input("duration_code"))->get();
                    if(count($duration)>0){
                     $dt = new DateTime();
                    $newhistory=new Price_History;
                    $newhistory->starDate=$dt->format('Y-m-d (H:i:s)');
                     $newhistory->service_id=$service->id;
                     $newhistory->image_id=$newruta->id;
                     $newhistory->price=$request->input("price");
                     $newhistory->currency_id=$request->input("currency_id");
                     $newhistory->save();
                     foreach($duration as $durations){
                         $imageduration=new Image_Duration;
                         $imageduration->image_id=$newruta->id;
                         $imageduration->duration_id=$durations->id;
                         $imageduration->save();
                     }
                     return response()->json('Add Service-Images');
                   }else{
                          return response()->json('Duration not found');
                    }
                  }else{
                        return response()->json('Service not found');
              }
        }
    }


    public function ReturnStep10(Request $request)
    { $rule=[
           'service_id' => 'required|numeric',
        ];
      $validator=Validator::make($request->all(),$rule);
      if ($validator->fails()) {
            return response()->json($validator->errors()->all());
      }else{
              $getstep10=DB::table('service')
        ->join('price_history','price_history.service_id','=','service.id')
        ->join('image','image.service_id','=','service.id')
        ->join('image_duration','image_duration.image_id','=','image.id')
        ->join('duration','duration.id','=','image_duration.duration_id')
        ->join('currency','currency.id','=','price_history.currency_id')
        ->where('service.id','=',$request->input("service_id"))
        ->where('duration.languaje','=',$request->input("languaje"))
        ->select('image.ruta','image.description','price_history.price','currency.money','currency.symbol','duration.type')
        ->orderby('price_history.image_id','DESC')->take(1)->get();
           if(count($getstep10)>0){
                  return response()->json($getstep10);
          }else{
                return response()->json("Not Found");
          }
      }
    }

    public function AddNewWorkspaceStep11(Request $request){
        $rule=[
        'service_id'=>'required|numeric',
        'bool_smoke'=>'boolean',
        'bool_carbon'=>'boolean',
        'bool_first'=>'boolean',
        'bool_safety'=>'boolean',
        'bool_fire'=>'boolean'
    ];
    $validator=Validator::make($request->all(),$rule);
    if ($validator->fails()) {
            return response()->json($validator->errors()->all());
    }else{
        $service=Service::where('id',$request->input("service_id"))->first();
        if(count($service)>0){
          $val=Service_Emergency::where("service_id",$service->id)->first();
          if(count($val)==0){

           $newnote1=new Service_Emergency;
           $newnote1->service_id=$service->id;
           $newnote1->emergency_id=1;
           $newnote1->content=$request->input("desc_anything");
           $newnote1->save();

           $newnote10=new Service_Emergency;
           $newnote10->service_id=$service->id;
           $newnote10->emergency_id=11;
           $newnote10->content=$request->input("desc_anything");
           $newnote10->save();

           $newnote2=new Service_Emergency;
           $newnote2->service_id=$service->id;
           $newnote2->emergency_id=2;
           $newnote2->check=$request->input("bool_smoke");
           $newnote2->save();

           $newnote2=new Service_Emergency;
           $newnote2->service_id=$service->id;
           $newnote2->emergency_id=12;
           $newnote2->check=$request->input("bool_smoke");
           $newnote2->save();

           $newnote3=new Service_Emergency;
           $newnote3->service_id=$service->id;
           $newnote3->emergency_id=3;
           $newnote3->check=$request->input("bool_carbon");
           $newnote3->save();

           $newnote3=new Service_Emergency;
           $newnote3->service_id=$service->id;
           $newnote3->emergency_id=13;
           $newnote3->check=$request->input("bool_carbon");
           $newnote3->save();

           $newnote4=new Service_Emergency;
           $newnote4->service_id=$service->id;
           $newnote4->emergency_id=4;
           $newnote4->check=$request->input("bool_first");
           $newnote4->save();

           $newnote4=new Service_Emergency;
           $newnote4->service_id=$service->id;
           $newnote4->emergency_id=14;
           $newnote4->check=$request->input("bool_first");
           $newnote4->save();

           $newnote5=new Service_Emergency;
           $newnote5->service_id=$service->id;
           $newnote5->emergency_id=5;
           $newnote5->check=$request->input("bool_safety");
           $newnote5->save();

           $newnote5=new Service_Emergency;
           $newnote5->service_id=$service->id;
           $newnote5->emergency_id=15;
           $newnote5->check=$request->input("bool_safety");
           $newnote5->save();

           $newnote6=new Service_Emergency;
           $newnote6->service_id=$service->id;
           $newnote6->emergency_id=6;
           $newnote6->check=$request->input("bool_fire");
           $newnote6->save();

           $newnote6=new Service_Emergency;
           $newnote6->service_id=$service->id;
           $newnote6->emergency_id=16;
           $newnote6->check=$request->input("bool_fire");
           $newnote6->save();

           $newnote7=new Service_Emergency;
           $newnote7->service_id=$service->id;
           $newnote7->emergency_id=7;
           $newnote7->content=$request->input("desc_fire");
           $newnote7->save();

           $newnote7=new Service_Emergency;
           $newnote7->service_id=$service->id;
           $newnote7->emergency_id=17;
           $newnote7->content=$request->input("desc_fire");
           $newnote7->save();

           $newnote8=new Service_Emergency;
           $newnote8->service_id=$service->id;
           $newnote8->emergency_id=8;
           $newnote8->content=$request->input("desc_alarm");
           $newnote8->save();

           $newnote8=new Service_Emergency;
           $newnote8->service_id=$service->id;
           $newnote8->emergency_id=18;
           $newnote8->content=$request->input("desc_alarm");
           $newnote8->save();

           $newnote9=new Service_Emergency;
           $newnote9->service_id=$service->id;
           $newnote9->emergency_id=9;
           $newnote9->content=$request->input("desc_gas");
           $newnote9->save();

           $newnote9=new Service_Emergency;
           $newnote9->service_id=$service->id;
           $newnote9->emergency_id=19;
           $newnote9->content=$request->input("desc_gas");
           $newnote9->save();

           $newnote10=new Service_Emergency;
           $newnote10->service_id=$service->id;
           $newnote10->emergency_id=10;
           $newnote10->content=$request->input("desc_exit");
           $newnote10->save();

           $newnote10=new Service_Emergency;
           $newnote10->service_id=$service->id;
           $newnote10->emergency_id=20;
           $newnote10->content=$request->input("desc_exit");
           $newnote10->save();

           return response()->json('Add Note emergency');
         }else{
                $val=DB::table('service_emergency')->where('service_id',$service->id)->delete();

           $newnote1=new Service_Emergency;
           $newnote1->service_id=$service->id;
           $newnote1->emergency_id=1;
           $newnote1->content=$request->input("desc_anything");
           $newnote1->save();

           $newnote1=new Service_Emergency;
           $newnote1->service_id=$service->id;
           $newnote1->emergency_id=11;
           $newnote1->content=$request->input("desc_anything");
           $newnote1->save();

           $newnote2=new Service_Emergency;
           $newnote2->service_id=$service->id;
           $newnote2->emergency_id=2;
           $newnote2->check=$request->input("bool_smoke");
           $newnote2->save();

           $newnote2=new Service_Emergency;
           $newnote2->service_id=$service->id;
           $newnote2->emergency_id=12;
           $newnote2->check=$request->input("bool_smoke");
           $newnote2->save();

           $newnote3=new Service_Emergency;
           $newnote3->service_id=$service->id;
           $newnote3->emergency_id=3;
           $newnote3->check=$request->input("bool_carbon");
           $newnote3->save();

           $newnote3=new Service_Emergency;
           $newnote3->service_id=$service->id;
           $newnote3->emergency_id=13;
           $newnote3->check=$request->input("bool_carbon");
           $newnote3->save();

           $newnote4=new Service_Emergency;
           $newnote4->service_id=$service->id;
           $newnote4->emergency_id=4;
           $newnote4->check=$request->input("bool_first");
           $newnote4->save();

           $newnote4=new Service_Emergency;
           $newnote4->service_id=$service->id;
           $newnote4->emergency_id=14;
           $newnote4->check=$request->input("bool_first");
           $newnote4->save();

           $newnote5=new Service_Emergency;
           $newnote5->service_id=$service->id;
           $newnote5->emergency_id=5;
           $newnote5->check=$request->input("bool_safety");
           $newnote5->save();

           $newnote5=new Service_Emergency;
           $newnote5->service_id=$service->id;
           $newnote5->emergency_id=15;
           $newnote5->check=$request->input("bool_safety");
           $newnote5->save();

           $newnote6=new Service_Emergency;
           $newnote6->service_id=$service->id;
           $newnote6->emergency_id=6;
           $newnote6->check=$request->input("bool_fire");
           $newnote6->save();

           $newnote6=new Service_Emergency;
           $newnote6->service_id=$service->id;
           $newnote6->emergency_id=16;
           $newnote6->check=$request->input("bool_fire");
           $newnote6->save();

           $newnote7=new Service_Emergency;
           $newnote7->service_id=$service->id;
           $newnote7->emergency_id=7;
           $newnote7->content=$request->input("desc_fire");
           $newnote7->save();

           $newnote7=new Service_Emergency;
           $newnote7->service_id=$service->id;
           $newnote7->emergency_id=17;
           $newnote7->content=$request->input("desc_fire");
           $newnote7->save();

           $newnote8=new Service_Emergency;
           $newnote8->service_id=$service->id;
           $newnote8->emergency_id=8;
           $newnote8->content=$request->input("desc_alarm");
           $newnote8->save();

           $newnote8=new Service_Emergency;
           $newnote8->service_id=$service->id;
           $newnote8->emergency_id=18;
           $newnote8->content=$request->input("desc_alarm");
           $newnote8->save();

           $newnote9=new Service_Emergency;
           $newnote9->service_id=$service->id;
           $newnote9->emergency_id=9;
           $newnote9->content=$request->input("desc_gas");
           $newnote9->save();

           $newnote9=new Service_Emergency;
           $newnote9->service_id=$service->id;
           $newnote9->emergency_id=19;
           $newnote9->content=$request->input("desc_gas");
           $newnote9->save();

           $newnote10=new Service_Emergency;
           $newnote10->service_id=$service->id;
           $newnote10->emergency_id=10;
           $newnote10->content=$request->input("desc_exit");
           $newnote10->save();

           $newnote10=new Service_Emergency;
           $newnote10->service_id=$service->id;
           $newnote10->emergency_id=20;
           $newnote10->content=$request->input("desc_exit");
           $newnote10->save();

            return response()->json('Update Note emergency');
        }

        }else{
            return response()->json('Service not found');
        }
    }
    }

    public function ReturnStep11(Request $request)
    {
          $rule=[
           'service_id' => 'required|numeric',
           'languaje'=>'required'
        ];
      $validator=Validator::make($request->all(),$rule);
      if ($validator->fails()) {
            return response()->json($validator->errors()->all());
      }else{
             $getstep11=DB::table('service')
                          ->join('service_emergency','service_emergency.service_id','=','service.id')
                          ->join('note_emergency','note_emergency.id','=','service_emergency.emergency_id')
                          ->where('service.id','=',$request->input("service_id"))
                          ->where('note_emergency.languaje','=',$request->input("languaje"))
                          ->select('service_emergency.content','service_emergency.check','note_emergency.type','service_emergency.emergency_id')
                          ->get();
          if(count($getstep11)>0){
                  return response()->json($getstep11);
          }else{
                return response()->json("Not Found");
          }
      }
    }

	    
 public function AddNewEmergency(Request $request)
 {
     $rule=[
        'service_id'=>'required|numeric',
        'number'=>'required|numeric|min:1',
        'name'=>'required|string'
    ];
    $validator=Validator::make($request->all(),$rule);
    if ($validator->fails()) {
            return response()->json($validator->errors()->all());
    }else{
        $service=Service::where('id',$request->input("service_id"))->first();
        if(count($service)>0){
            $newnumber=New Emergency_Number;
            $newnumber->service_id=$service->id;
            $newnumber->name=$request->input("name");
            $newnumber->number=$request->input("number");
            $newnumber->save();
            return response()->json($newnumber);
        }else{
            return response()->json('Service not found');
        }
    }
 }

 public function DeleteNewEmergency(Request $request)
 { $rule=[
        'service_id'=>'numeric',
        'number_id'=>'numeric'
    ];
    $validator=Validator::make($request->all(),$rule);
    if ($validator->fails()) {
            return response()->json($validator->errors()->all());
    }else{
       $number=DB::table('number_emergency')->where('service_id',$request->input("service_id"))->where('id',$request->input("number_id"))->delete();
       if($number!=0){
             return response()->json('Number Emergency Delete!');
       }else{
              return response()->json('Number Emergency not found');
       }
    }

 }

    public function GetNumberEmergency(Request $request)
    {   $rule=[
           'service_id' => 'required|numeric'
        ];
      $validator=Validator::make($request->all(),$rule);
      if ($validator->fails()) {
            return response()->json($validator->errors()->all());
      }else{
          $number=DB::table('number_emergency')->where('service_id',$request->input("service_id"))->get();
          if(count($number)>0){
                  return response()->json($number);
          }else{
                return response()->json("Not Found");
          }
      }
    }


		     /*--------------PREVIEW 1-----------------*/
		    public function GetOverviews(Request $request)
		    {
		    	$rule=[
		           'service_id' => 'required|numeric',
		           'languaje'=>'required',
		        ];
		      $validator=Validator::make($request->all(),$rule);
		      if ($validator->fails()) {
		            return response()->json($validator->errors()->all());
		      }else{

		             $previews=DB::table('service')
		             ->join('user','user.id','=','service.user_id')
		             ->join('city','service.city_id','=','city.id')
		             ->join('state','city.state_id','=','state.id')
		             ->join('country','country.id','=','state.country_id')
		             ->join('service_description','service_description.service_id','=','service.id')
		             ->join('description','description.id','=','service_description.description_id')
		        //     ->join('check_in','check_in.service_id','=','service.id')
		             ->join('service_category','service_category.service_id','=','service.id')
		             ->join('category','category.id','=','service_category.category_id')
		      //       ->join('service_payment','service_payment.service_id','=','service.id')
		     //        ->join('payment','payment.id','=','service_payment.payment_id')
             		 ->join('service_type','service_type.service_id','=','service.id')
             		 ->join('type','type.id','=','service_type.type_id')
		             ->where('service.id','=',$request->input("service_id"))
		             ->where('category.languaje','=',$request->input("languaje"))
		        //     ->where('payment.languaje','=',$request->input("languaje"))
		             ->where('description.id','=',1)
		             ->where('category.code','=',3)
            		 ->where('type.languaje','=',$request->input("languaje"))
		             ->select('service.user_id', 'user.id as userid','user.avatar','user.name','country.name as country',/*'payment.type as prices',*/'state.name as state','service_description.content as title',/*'check_in.time_entry as check_in',*/'category.name as category','user.lastname','service.id','city.name as city','service.num_space','type.name as type')
		             ->first();
		               if(count($previews)>0){
		                  return response()->json($previews);
		          }else{
		                return response()->json("Not Found");
		          }

		      }

		    }


    public function GetOverviewsRules(Request $request)
    {
      $rule=[
           'service_id' => 'required|numeric',
        ];
      $validator=Validator::make($request->all(),$rule);
      if ($validator->fails()) {
            return response()->json($validator->errors()->all());
      }else{
              $previews=DB::table('service')
                ->join('service_rules','service_rules.service_id','=','service.id')
                ->join('house_rules','house_rules.id','=','service_rules.rules_id')
                ->where('service.id','=',$request->input("service_id"))
                ->select('house_rules.type','service_rules.description','service_rules.check')
                ->get();
                if(count($previews)>0){
                  return response()->json($previews);
          }else{
                return response()->json("Not Found");
          }


      }
    }

    public function GetOverviewsAmenities(Request $request)
    {
          $rule=[
           'service_id' => 'required|numeric',
           'languaje'=>'required'
        ];
      $validator=Validator::make($request->all(),$rule);
      if ($validator->fails()) {
            return response()->json($validator->errors()->all());
      }else{

              $previews=DB::table('service')
                ->join('service_amenites','service_amenites.service_id','=','service.id')
                ->join('amenities','amenities.id','=','service_amenites.amenite_id')
                ->where('service.id','=',$request->input("service_id"))
                ->where('amenities.languaje','=',$request->input("languaje"))
              //  ->where('service_amenites.check',1)
                ->select('amenities.name as amenities','amenities.code','amenities.type_amenities_id','service_amenites.check')
                ->get();
                  if(count($previews)>0){
                  return response()->json($previews);
          }else{
                return response()->json("Not Found");
          }

      }

    }

    public function GetOverviewsEmergencyNote(Request $request)
    { $rule=[
           'service_id' => 'required|numeric',
           'languaje'=>'required'
        ];
      $validator=Validator::make($request->all(),$rule);
      if ($validator->fails()) {
            return response()->json($validator->errors()->all());
      }else{
              $previews=DB::table('service')
                ->join('service_emergency','service_emergency.service_id','=','service.id')
                ->join('note_emergency','note_emergency.id','=','service_emergency.emergency_id')
                    ->where('service.id','=',$request->input("service_id"))
                    ->where('note_emergency.languaje','=',$request->input("languaje"))
                ->select('note_emergency.type','service_emergency.content','service_emergency.check')
                ->get();
                            if(count($previews)>0){
                  return response()->json($previews);
          }else{
                return response()->json("Not Found");
          }

      }
    }

 public function getDescription(Request $request){
           $rule=[
           'service_id' => 'required|numeric'
      ];
    $validator=Validator::make($request->all(),$rule);
      if ($validator->fails()) {
            return response()->json($validator->errors()->all());
      }else{
        $des=DB::table('service')
        ->join('service_description','service_description.service_id','=','service.id')
        ->join('description','description.id','=','service_description.description_id')
        ->where('service_description.service_id','=',$request->input("service_id"))
        ->where('service_description.description_id', '=', 8)
        ->orwhere('service_description.description_id', '=', 5)
        ->where('service_description.service_id','=',$request->input("service_id"))
        ->select('service_description.content','service_description.check','service_description.description_id')
        ->get();
          if(count($des)>0){
                return response()->json($des);
        }else{
                return response()->json("Not Found");
        }

      }
    }

    /*--------------------PREVIEW 4------------------------*/
	    public function GetLocationMap(Request $request)
	    {$rule=[
	           'service_id' => 'required|numeric',
	        ];
	      $validator=Validator::make($request->all(),$rule);
	      if ($validator->fails()) {
	            return response()->json($validator->errors()->all());
	      }else{

	              $previews=DB::table('service')
	              ->join('service_description','service_description.service_id','=','service.id')
	                ->join('city','service.city_id','=','city.id')
	                ->join('state','city.state_id','=','state.id')
	                ->join('country','country.id','=','state.country_id')
	                 ->where('service.id','=',$request->input("service_id"))
	                   ->select('service_description.id','country.name as country','state.name as state','city.name as city')
	                   ->first();
	                     if(count($previews)>0){
	                  return response()->json($previews);
	          }else{
	                return response()->json("Not Found");
	          }

	      }
	    }

	    public function GetLocationMapLongitude(Request $request)
	    {$rule=[
	           'service_id' => 'required|numeric',
	        ];
	      $validator=Validator::make($request->all(),$rule);
	      if ($validator->fails()) {
	            return response()->json($validator->errors()->all());
	      }else{

	              $previews=DB::table('service')
	                ->join('service_description','service_description.service_id','=','service.id')
	                 ->join('description','description.id','=','service_description.description_id')
	                 ->where('service.id','=',$request->input("service_id"))
	                  ->where('description.id','=',6)
	                  //->where('description.id','=',10)
	                   ->select('service_description.content')
	                   ->first();
	                     if(count($previews)>0){
	                  return response()->json($previews);
	          }else{
	                return response()->json("Not Found");
	          }

	      }
	    }

	    public function GetLocationMapLatitude(Request $request)
	    {$rule=[
	           'service_id' => 'required|numeric',
	        ];
	      $validator=Validator::make($request->all(),$rule);
	      if ($validator->fails()) {
	            return response()->json($validator->errors()->all());
	      }else{

	              $previews=DB::table('service')
	                ->join('service_description','service_description.service_id','=','service.id')
	                 ->join('description','description.id','=','service_description.description_id')
	                 ->where('service.id','=',$request->input("service_id"))
	                  ->where('description.id','=',7)
	                  //->where('description.id','=',10)
	                   ->select('service_description.content')
	                   ->first();
	                     if(count($previews)>0){
	                  return response()->json($previews);
	          }else{
	                return response()->json("Not Found");
	          }

	      }
	    }




	}
