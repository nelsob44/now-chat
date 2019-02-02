<template>
    <v-container>
        <div class="container" @hide="hideForSlideShow" v-if="isSliding">
            <div class="row justify-content-center">
                <div class="col-md-3">
                    <div class="card card-default">
                        <div class="card-header">
                            Now Chat 
                        </div>
                            <ul class="list-group">
                                <li class="list-group-item" @click.prevent="openChat(friend)" 
                                v-for="friend in friends" :key="friend.id">
                                    <a href="" >
                                        {{friend.name}}
                                        <span class="text-danger" v-if="friend.session &&
                                        (friend.session.unreadCount > 0)">{{friend.session.unreadCount}}
                                        </span>
                                    </a>
                                    <i class="fa fa-circle float-right text-success" aria-hidden="true"
                                    v-if="friend.online">&nbsp;<small>active</small></i>
                                </li>
                            </ul>
                    </div>
                </div>
                <div class="col-md-9">
                    <span v-for="friend in friends" :key="friend.id" v-if="friend.session">
                        <message-component v-if="friend.session.open" @close="close(friend)" :friend=friend></message-component>
                    </span>
                </div>
            </div>
        </div>

        <v-container fluid>
            <v-layout row wrap v-if="singleImage">
                <v-flex xs12>
                    <v-card>
                        <v-card-title>
                            <a href="" @click.prevent="closeSingleImage">
                                <i class="fa fa-times float-right" aria-hidden="true"></i>
                            </a>
                        </v-card-title>
                        <v-card-media
                        :src="'http://localhost/now-chat/public/images/' + pic" height="auto">
                        </v-card-media>

                        <!-- <v-card-title primary-title>
                        <div>
                            <div class="headline">Top western road trips</div>
                            <span class="grey--text">1,000 miles of wonder</span>
                        </div>
                        </v-card-title> -->
                    
                    </v-card>
                </v-flex>
            </v-layout>
        </v-container>

        <v-container v-if="carouselImages">
            <v-carousel>
                <v-carousel-item v-for="chat in chats"
                :src="'http://localhost/now-chat/public/images/' + chat.image" v-if="chat.stringlength < 30 && chat.image!=null" :key="chat.id">
                    
                    <a href="" @click.prevent="closeCarousel">
                        <i class="fa fa-times float-right" aria-hidden="true"></i>
                    </a>
                    
                    <h1>Carousels</h1>
                </v-carousel-item>
            </v-carousel>
        </v-container>
        
    </v-container>
    
</template>

<script>
    import MessageComponent from './MessageComponent';
    export default {
        data(){
            return {
                open: true,
                friends: [],
                isSliding: true,
                // seeCarousel: false,
                pic: null,
                singleImage: false,
                chats: [],
                carouselImages: false
            }
        },
        components:{MessageComponent},

        methods:{
            close(friend){
                friend.session.open = false;
            },
            closeSingleImage(){
                this.singleImage = false;
                this.isSliding = true;
            },
            getFriends(){
                axios.post('getFriends').then(res => {
                    this.friends = res.data.data;
                    this.friends.forEach(
                        friend => (friend.session ? this.listenForEverySession(friend) : "") 
                    );
                });
            },
            hideForSlideShow(){
                this.isSliding = false;
               
            },
            closeCarousel(){
                this.isSliding = true;
                
                this.carouselImages = false;
            },

            showAfterSlideShow(){
                this.isSliding = true;
                this.seeCarousel = false;
            },
            openChat(friend){
                if(friend.session){
                    this.friends.forEach(
                    friend => (friend.session ? (friend.session.open = false) : '')
                );
                friend.session.open = true;
                friend.session.unreadCount = 0;
                }else {
                    this.createSession(friend);                    
                }
            },
            
            createSession(friend){
                axios.post('session/create', {friend_id:friend.id}).then(res => {
                    (friend.session = res.data.data), (friend.session.open = true);
                });
            },

            listenForEverySession(friend){
                Echo.private(`Chat.${friend.session.id}`).listen(
                    "PrivateChatEvent", 
                    e => (friend.session.open ? "" : friend.session.unreadCount++)
                );
            }
        },
        created(){
            this.getFriends();

            Echo.channel("Chat").listen("SessionEvent", e => {
                let friend = this.friends.find(friend => friend.id == e.session_by);
                friend.session = e.session;
                this.listenForEverySession(friend);
            });

            Echo.join(`Chat`)
            .here((users) => {
                this.friends.forEach(friend => {
                    users.forEach(user => {
                        if(user.id == friend.id) {
                            friend.online = true;
                        }
                    })
                })
            })
            .joining((user) => {
                this.friends.forEach(friend => user.id == friend.id ? friend.online = true: '')
            })
            .leaving((user) => {
                this.friends.forEach(friend => user.id == friend.id ? friend.online = true: '')
            });

            EventBus.$on('hide', (chat) => {
                this.hideForSlideShow();
                this.pic = chat.image;
                this.singleImage = true;
            });

            EventBus.$on('hideforcarousel', (items) => {
                this.chats = items;
                this.carouselImages = true;
                this.isSliding = false;
                
            });
           
        },
                
    }
</script>
