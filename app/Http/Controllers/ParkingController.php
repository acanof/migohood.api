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
	use App\Models\Accommodation;
	use App\Models\Bedroom;
	use App\Models\Bedroom_Bed;
	use App\Models\Service_Duration;

	class ParkingController extends Controller {
		
		//Muestra todo los service
	    public function ReadService(){
	    //Se obtiene todos los servicios que se crean
	    return Service::all();
    	}

    	public function AddNewParkingStep(Request $request){
	         $rule=[
	            'user_id' => 'required|numeric|min:1',
	            'category_code'=>'required|numeric|min:1',
	        ];
	        $validator=Validator::make($request->all(),$rule);
	        if ($validator->fails()) {
	            return response()->json($validator->errors()->all());
	        }else{
              $user=User::select()->where('id',$request->input("user_id"))->first();
              $code=3;
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
		              ->where('service_category.category_id','=','3')
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
	                $type=Type::select('id','name','code')->where('category_id','=',3)->where('languaje','=',$request->input("languaje"))->get();
	                if(count($type)>0){
	                      return response()->json($type);
	                }else{
	                      return response()->json("Type not found");
	                }
	          }
	    }

	//Agregar Place type(parking-step1)-Web
	    public function AddNewParkingStep1(Request $request){
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
	            $type=Type::select('id')->where('category_id','=',3)->where('code','=',$request->input("type_code"))->get();
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
    public function AddNewParkingStep2(Request $request){
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
            /* Buscas si existen habitaciones previamente y cuantas habitaciones hay y sus respectivos id, si el numero que se esta pasando es mayor entonces agregas el restante, si es menor debes eliminar las sobrantes */
            // Habia un error, la variable se llama servicespace, no service
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
                        // Habia un error, la variable se llama servicespace, no service
                        $bedroom->service_id=$servicespace->id;
                        $bedroom->save();
                    }
                }else{
                    DB::table('bedroom')->where('service_id',$servicespace->id)->delete();
                    for($i=1;$i<=$request->input("num_bedroom");$i++){
                        $bedroom=new Bedroom;
                        // Habia un error, la variable se llama servicespace, no service
                        $bedroom->service_id=$servicespace->id;
                        $bedroom->save();
                    }
                }
                /**
                *   Envio como respuesta el servicio junto con el numero de habitaciones,
                *   ya que para la vista siguiente son necesarios dichos datos
                */
                return response()->json("Add Space Bedroom");

            } else {
                return response()->json('Service not found');
            }
        }

   }

   //Agregar Service(space-step2-beds)-Web
   public function AddNewParkingStep2Beds(Request $request){
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

		public function AddNewParkingStep4(Request $request){
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

	    //Agrega(step5) a un service amenities nota:solo category 1 y 2 tienen amenities
	    public function AddNewStep5(Request $request){
	        $rule=[
	            'service_id'=>'required|numeric|min:1',
	            'security'=>'boolean',
	            'padlock'=>'boolean',
	            'vigilant'=>'boolean',
	            'permission'=>'boolean',
	            'valet'=>'boolean',
	            'other'=>'boolean',
	        ];
	        $validator=Validator::make($request->all(),$rule);
	        if ($validator->fails()) {
	            return response()->json($validator->errors()->all());
	        }else{
	            // Selecciono los amenites que posean el código recibido
	            $amenites=Amenite::select('id')->where('category_id','=',3)->get();
	            if(count($amenites)>0){
	                // Selecciono el service que posea el id recibido
	                $service=Service::select()->where('id',$request->input("service_id"))->first();
	                if(count($service)>0){
	                    try{
	                    	DB::table('service_amenites')->where('service_id',$service->id)->delete();
		                 $newamenites=new Service_Amenite;
		                 $newamenites->service_id=$service->id;
		                 $newamenites->amenite_id=42;
		                 $newamenites->check=$request->input("security");
		                 $newamenites->save();
		                 $newamenites=new Service_Amenite;
		                 $newamenites->service_id=$service->id;
		                 $newamenites->amenite_id=44;
		                 $newamenites->check=$request->input("padlock");
		                 $newamenites->save();
		                 $newamenites=new Service_Amenite;
		                 $newamenites->service_id=$service->id;
		                 $newamenites->amenite_id=46;
		                 $newamenites->check=$request->input("vigilant");
		                 $newamenites->save();
		                 $newamenites=new Service_Amenite;
		                 $newamenites->service_id=$service->id;
		                 $newamenites->amenite_id=48;
		                 $newamenites->check=$request->input("permission");
		                 $newamenites->save();
		                 $newamenites=new Service_Amenite;
		                 $newamenites->service_id=$service->id;
		                 $newamenites->amenite_id=50;
		                 $newamenites->check=$request->input("valet");
		                 $newamenites->save();
		                 $newamenites=new Service_Amenite;
		                 $newamenites->service_id=$service->id;
		                 $newamenites->amenite_id=52;
		                 $newamenites->check=$request->input("other");
		                 $newamenites->save();
		                 DB::table('service_rules')->where('service_id',$service->id)->where('rules_id',13)->delete();
		                 $newrule=new Service_Rules;
		                 $newrule->service_id=$service->id;
		                 $newrule->rules_id=13;
		                 $newrule->description=$request->input("instruction");
		                 $newrule->save();
		                  return response()->json('Update Step-5');
	                      }catch(Exception $e){
	                       return response()->json($e);
	                }
	            	}else{
	            		 $newamenites=new Service_Amenite;
		                 $newamenites->service_id=$service->id;
		                 $newamenites->amenite_id=42;
		                 $newamenites->check=$request->input("security");
		                 $newamenites->save();
		                 $newamenites=new Service_Amenite;
		                 $newamenites->service_id=$service->id;
		                 $newamenites->amenite_id=44;
		                 $newamenites->check=$request->input("padlock");
		                 $newamenites->save();
		                 $newamenites=new Service_Amenite;
		                 $newamenites->service_id=$service->id;
		                 $newamenites->amenite_id=46;
		                 $newamenites->check=$request->input("vigilant");
		                 $newamenites->save();
		                 $newamenites=new Service_Amenite;
		                 $newamenites->service_id=$service->id;
		                 $newamenites->amenite_id=48;
		                 $newamenites->check=$request->input("permission");
		                 $newamenites->save();
		                 $newamenites=new Service_Amenite;
		                 $newamenites->service_id=$service->id;
		                 $newamenites->amenite_id=50;
		                 $newamenites->check=$request->input("valet");
		                 $newamenites->save();
		                 $newamenites=new Service_Amenite;
		                 $newamenites->service_id=$service->id;
		                 $newamenites->amenite_id=52;
		                 $newamenites->check=$request->input("other");
		                 $newamenites->save();
		                 $newrule=new Service_Rules;
		                 $newrule->service_id=$service->id;
		                 $newrule->rules_id=13;
		                 $newrule->description=$request->input("instruction");
		                 $newrule->save();
	                    return response()->json('Add Step-5');
	                }

	                }
	                else{
	                    return response()->json('Service not found');
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
		        ->select('service.id','service_amenites.check as Check','service_amenites.amenite_id')
		        ->get();
		        if(count($getstep5)>0){
		                return response()->json($getstep5);
		        }else{
		                return response()->json("Not Found");
		        }
		      }
		    }

	    public function GetParkingAmenities(Request $request){
	      $rule=[
	           'languaje' => 'required'
	      ];
	      $validator=Validator::make($request->all(),$rule);
	      if ($validator->fails()) {
	            return response()->json($validator->errors()->all());
	      }else{
	            $amenitie = DB::table('amenities')->select('code','name','type_amenities_id')->where('languaje','=',$request->input("languaje"))->where('category_id','=',3)->where('amenities.code','=',$request->input("service_id"))->get();
	            if(count($amenitie)>0){
	                  return response()->json($amenitie);
	            }else{
	                  return response()->json("Amenities not found");
	            }
	      }
	    }

	    public function AddNewAmenities(Request $Request){
	    	$rule=[
	    	'service_id'=>'required|numeric',
	    	'name'=>'required|string'
	    	];
	    	$validator=Validator::make($request->all(),$rule);
	    	if ($validator->fails()) {
		            return response()->json($validator->errors()->all());
		    }else{
		        $service=Service::where('id',$request->input("service_id"))->first();
		        if(count($service)>0){
		        	$newamenites=New Amenities;
		        	$newamenites->name=$request->input("name");
		        	$newamenites->category_id=3;
		        	$newamenites->type_amenities_id=1;
		        	$newamenites->code=$request->input("service_id").'3';
		        	$newamenites->languaje=$request->input("languaje");
		        	$newamenites->save();
		        	return response()->json($newamenites);
		        }else{
	            return response()->json('Service not found');
	        }

		    }
		}

		 public function DeleteNewAmenities(Request $request)
		 { $rule=[
		        'service_id'=>'numeric',
		        'number_id'=>'numeric'
		    ];
		    $validator=Validator::make($request->all(),$rule);
		    if ($validator->fails()) {
		            return response()->json($validator->errors()->all());
		    }else{
		       $number=DB::table('amenities')->where('code',$request->input("service_id").'3')->where('id',$request->input("number_id"))->delete();
		       if($number!=0){
		             return response()->json('Amenities Delete!');
		       }else{
		              return response()->json('Amenities not found');
		       }
		    }

		 }


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

	    public function AddNewParkingStep6(Request $request){
	    	//
	    }

	    public function ReturnStep6(Request $request){
	    	//
	    }

		public function AddNewParkingStep7(Request $request){
		          $rule=[
		           'service_id' => 'required|numeric|min:1',
		           'bool_socialize'=>'boolean',
		           'bool_available'=>'boolean'
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
		                  return response()->json('Add Step-7');
		                }catch(Exception $e){
		                    return response()->json($e);
		               }
		            }else{
		              $serv_des= Service_Description::get();
		              DB::table('service_description')->where('service_id',$service->id)->where('description_id','1')->delete();
		              foreach ($serv_des as $sd) {
		                if($sd->description_id == 8 OR $sd->description_id == 9 OR $sd->description_id == 10){
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


   public function AddNewSpaceStep8Rules(Request $request){
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
                 $newrules->description=Crypt::encrypt($request->input("Password_Wifi"));
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
                 $newrules->description=Crypt::encrypt($request->input("Password_Wifi"));
                 $newrules->save();
                 return response()->json('Update Step-8');

                }
            }else{
                 return response()->json('Service not found');
            }
       }
   }

    public function ReturnStep8(Request $request){
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




	}
